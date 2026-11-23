<?php

namespace App\Utils;

use App\Models\Account;
use App\Models\Charge;
use App\Models\ChargeCategory;
use App\Models\CoverageRule;
use App\Models\Doctor;
use App\Models\DoctorFeePlan;
use App\Models\DoctorOPDCharge;
use App\Models\ItemPrice;
use App\Models\Operation;
use App\Models\OperationCategory;
use App\Models\PriceList;
use App\Models\DataImportRun;
use App\Models\DoctorDepartment;
use App\Models\Insurance;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\MasterListItem;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Go-live import of the starting data (from the old program or typed in a spreadsheet).
 * Every file is checked first (preview, nothing written); a run never creates a record twice,
 * so the same file can be imported again after fixing the lines that failed.
 */
class DataImport
{
    // In the order a new hospital fills them.
    const KINDS = ['departments', 'doctors', 'staff', 'insurers', 'coverage_rules', 'suppliers', 'tariff', 'surgical_acts', 'patients', 'policies', 'opening_stock', 'opening_balances'];

    // Column titles accepted for each field (compared lower-case, without accents, spaces as "_"),
    // besides the field labels of every installed language.
    const COLUMNS = [
        'departments' => ['name' => ['name', 'nom', 'department', 'service', 'specialite'], 'discount_percent' => ['discount', 'discount_percent', 'reduction', 'remise'],
            'description' => ['description']],
        'tariff' => ['type' => ['type', 'nature'], 'act' => ['act', 'name', 'acte', 'libelle', 'designation', 'code', 'prestation'],
            'category' => ['category', 'categorie', 'charge_category'], 'charge_type' => ['charge_type', 'type_de_charge'],
            'price' => ['price', 'prix', 'tarif', 'comptant', 'cash', 'standard_charge'], 'doctor_share' => ['doctor_share', 'part_medecin'],
            'assistant_share' => ['assistant_share', 'part_assistant']],
        'surgical_acts' => ['name' => ['name', 'nom', 'acte', 'intervention'], 'category' => ['category', 'categorie'],
            'k_surgeon' => ['k_surgeon', 'k_chirurgien', 'k'], 'k_assistant' => ['k_assistant'], 'k_anaesthetist' => ['k_anaesthetist', 'k_anesthesiste']],
        'coverage_rules' => ['insurer' => ['insurer', 'insurance', 'assureur', 'assurance', 'societe'], 'department' => ['department', 'service', 'specialite'],
            'category' => ['category', 'categorie', 'charge_category'], 'coverage_percent' => ['coverage', 'coverage_percent', 'taux', 'prise_en_charge'],
            'not_covered' => ['not_covered', 'non_couvert', 'exclu'], 'needs_approval' => ['needs_approval', 'accord_prealable', 'entente_prealable'],
            'max_amount' => ['max_amount', 'plafond', 'montant_max']],
        'patients' => ['number' => ['number', 'patient_number', 'numero', 'n_dossier', 'dossier', 'code'], 'last_name' => ['last_name', 'nom'],
            'first_name' => ['first_name', 'prenom', 'prenoms'], 'gender' => ['gender', 'sex', 'sexe'],
            'dob' => ['dob', 'birth_date', 'date_of_birth', 'date_naissance', 'date_de_naissance', 'ne_le'], 'phone' => ['phone', 'telephone', 'tel', 'mobile', 'contact'],
            'email' => ['email', 'e_mail', 'mail'], 'id_document_type' => ['id_document_type', 'type_piece', 'piece'],
            'id_document_number' => ['id_document_number', 'numero_piece', 'n_piece', 'cnib'], 'address1' => ['address', 'address1', 'adresse'], 'city' => ['city', 'ville']],
        'policies' => ['patient' => ['patient', 'patient_number', 'numero', 'n_dossier', 'dossier'], 'insurer' => ['insurer', 'insurance', 'payer', 'assurance', 'assureur', 'societe'],
            'policy_number' => ['policy_number', 'police', 'n_police'], 'member_number' => ['member_number', 'matricule', 'adherent', 'n_adherent'],
            'plan_name' => ['plan', 'plan_name', 'formule'], 'coverage_percent' => ['coverage', 'coverage_percent', 'taux', 'prise_en_charge', 'taux_prise_en_charge'],
            'valid_from' => ['valid_from', 'debut', 'date_debut'], 'valid_to' => ['valid_to', 'fin', 'date_fin', 'expiration']],
        'doctors' => ['last_name' => ['last_name', 'nom'], 'first_name' => ['first_name', 'prenom', 'prenoms'], 'email' => ['email', 'e_mail', 'mail'],
            'gender' => ['gender', 'sex', 'sexe'], 'phone' => ['phone', 'telephone', 'tel', 'mobile'], 'dob' => ['dob', 'birth_date', 'date_naissance'],
            'department' => ['department', 'speciality', 'specialite', 'service'], 'specialist' => ['specialist', 'specialisation'],
            'qualification' => ['qualification', 'diplome'], 'designation' => ['designation', 'titre', 'fonction'],
            'appointment_charge' => ['appointment_charge', 'fee', 'tarif', 'prix_consultation'], 'pay_type' => ['pay_type', 'mode_de_paiement', 'remuneration'],
            'pay_rate' => ['pay_rate', 'rate', 'taux', 'montant'], 'withholding_exempt' => ['withholding_exempt', 'exonere', 'sans_retenue']],
        'staff' => ['role' => ['role', 'profil', 'poste', 'categorie', 'category'], 'last_name' => ['last_name', 'nom'], 'first_name' => ['first_name', 'prenom', 'prenoms'],
            'email' => ['email', 'e_mail', 'mail'], 'gender' => ['gender', 'sex', 'sexe'], 'phone' => ['phone', 'telephone', 'tel', 'mobile'],
            'dob' => ['dob', 'birth_date', 'date_naissance'], 'designation' => ['designation', 'titre', 'fonction'], 'qualification' => ['qualification', 'diplome']],
        'insurers' => ['name' => ['name', 'nom', 'raison_sociale'], 'code' => ['code', 'insurance_code'], 'payer_type' => ['payer_type', 'type'],
            'coverage_percent' => ['coverage', 'coverage_percent', 'taux'], 'discount' => ['discount', 'reduction', 'remise'],
            'contact_person' => ['contact_person', 'contact', 'interlocuteur'],
            'phone' => ['phone', 'telephone', 'tel'], 'email' => ['email', 'e_mail', 'mail'], 'address' => ['address', 'adresse'],
            'credit_days' => ['credit_days', 'delai', 'delai_paiement'], 'claim_note' => ['claim_note', 'note', 'observations']],
        'suppliers' => ['name' => ['name', 'nom', 'raison_sociale'], 'contact_person' => ['contact_person', 'contact', 'interlocuteur'],
            'phone' => ['phone', 'telephone', 'tel'], 'email' => ['email', 'e_mail', 'mail'], 'address' => ['address', 'adresse'],
            'tax_number' => ['tax_number', 'ifu', 'nif', 'tax_id']],
        'opening_stock' => ['medicine' => ['medicine', 'name', 'nom', 'medicament', 'produit', 'designation'], 'code' => ['code', 'cis', 'cis_code', 'code_cis', 'barcode', 'code_barre'],
            'store' => ['store', 'depot', 'magasin'], 'batch_no' => ['batch_no', 'batch', 'lot', 'n_lot'], 'expiry_date' => ['expiry_date', 'expiry', 'peremption', 'date_peremption', 'expiration'],
            'quantity' => ['quantity', 'qty', 'quantite', 'qte', 'stock'], 'unit_cost' => ['unit_cost', 'cost', 'cout', 'prix_achat', 'pa', 'cmup']],
        'opening_balances' => ['account' => ['account', 'code', 'compte', 'n_compte'], 'label' => ['label', 'libelle', 'intitule'], 'debit' => ['debit'], 'credit' => ['credit']],
    ];

    const REQUIRED = ['departments' => ['name'], 'tariff' => ['type', 'act'], 'surgical_acts' => ['name'], 'coverage_rules' => ['insurer'], 'patients' => ['last_name', 'first_name', 'gender'], 'policies' => ['patient', 'insurer'], 'doctors' => ['last_name', 'first_name', 'email', 'gender', 'department'],
        'staff' => ['role', 'last_name', 'first_name', 'email', 'gender'], 'insurers' => ['name'], 'suppliers' => ['name'], 'opening_stock' => ['quantity'], 'opening_balances' => ['account']];

    // Staff role => [accepted words, repository that creates the account]
    const ROLES = [
        'nurse' => [['nurse', 'infirmier', 'infirmiere'], Repositories\NurseRepository::class],
        'receptionist' => [['receptionist', 'receptionniste', 'accueil', 'reception'], Repositories\ReceptionistRepository::class],
        'accountant' => [['accountant', 'comptable', 'caissier', 'caissiere', 'cashier'], Repositories\AccountantRepository::class],
        'pharmacist' => [['pharmacist', 'pharmacien', 'pharmacienne'], Repositories\PharmacistRepository::class],
        'lab_technician' => [['lab_technician', 'laborantin', 'laborantine', 'technicien_labo', 'laboratoire'], Repositories\LabTechnicianRepository::class],
    ];

    // Tariff type => words accepted in the "Type" column (ItemPrice::ITEM_TYPES keys).
    const TARIFF_TYPES = [
        'charge' => ['charge', 'act', 'acte', 'prestation', 'service', 'soin'],
        'doctor_opd_charge' => ['doctor_opd_charge', 'consultation', 'doctor', 'medecin'],
        'pathology_test' => ['pathology_test', 'lab', 'laboratoire', 'analyse', 'biologie', 'examen_labo'],
        'radiology_test' => ['radiology_test', 'imaging', 'imagerie', 'radiologie', 'radio', 'echographie'],
        'package' => ['package', 'forfait', 'bilan', 'check_up'],
        'bed' => ['bed', 'lit', 'chambre'],
    ];

    const YES = ['1', 'yes', 'y', 'oui', 'o', 'x', 'true', 'vrai'];

    const PREVIEW_ROWS = 50;

    private array $ctx = [];

    /** @param array $options store_id, prefix (phone country code), date and journal (opening balances) */
    public function __construct(public string $kind, public array $options = [])
    {
        abort_unless(in_array($kind, self::KINDS, true), 422);
    }

    public static function key(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', Str::ascii(mb_strtolower(trim($text)))), '_');
    }

    private static function locales(): array
    {
        return array_diff(array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR) ?: []), ['vendor']);
    }

    /** Price lists with their own column in the tariff file (the default list's price is the "Price" column). */
    private static function priceLists()
    {
        $default = PriceList::defaultList();

        return PriceList::where('is_active', true)->when($default, fn ($q) => $q->where('id', '!=', $default->id))->orderBy('sort_order')->orderBy('id')->get();
    }

    /** Field => accepted titles, including the field labels in every installed language. */
    public static function titles(string $kind): array
    {
        $titles = [];
        foreach (self::COLUMNS[$kind] as $field => $aliases) {
            foreach (self::locales() as $locale) {
                $aliases[] = self::key(__('messages.data_import.fields.'.$field, [], $locale));
            }
            $titles[$field] = array_unique($aliases);
        }
        if ($kind === 'tariff') {
            foreach (self::priceLists() as $list) {
                $names = [$list->name];
                foreach ($list->lang_key ? self::locales() : [] as $locale) {
                    $names[] = __($list->lang_key, [], $locale);
                }
                $titles['list_'.$list->id] = array_unique(array_map(fn ($n) => self::key((string) $n), $names));
            }
        }

        return $titles;
    }

    public static function fields(string $kind): array
    {
        return array_keys(self::titles($kind));
    }

    /** Column title of a field in the current language. */
    public static function label(string $field): string
    {
        if (str_starts_with($field, 'list_')) {
            $list = PriceList::find((int) substr($field, 5));

            return $list ? ($list->lang_key && __($list->lang_key) !== $list->lang_key ? __($list->lang_key) : $list->name) : $field;
        }

        return __('messages.data_import.fields.'.$field);
    }

    /** Title row of the template, in the current language. */
    public static function template(string $kind): array
    {
        return array_map(fn ($f) => self::label($f), self::fields($kind));
    }

    /** File rows => [line number => fields], and the titles that matched nothing. */
    public function map(array $rows): array
    {
        $lookup = [];
        foreach (self::titles($this->kind) as $field => $aliases) {
            foreach ($aliases as $alias) {
                $lookup[$alias] ??= $field;
            }
        }
        $mapped = [];
        $unknown = [];
        foreach ($rows as $i => $row) {
            $data = [];
            foreach ($row as $title => $value) {
                $field = $lookup[self::key((string) $title)] ?? null;
                if ($field === null) {
                    $unknown[(string) $title] = true;
                } elseif (trim((string) $value) !== '' && ! isset($data[$field])) {
                    $data[$field] = trim((string) $value);
                }
            }
            if ($data) {
                $mapped[$i + 2] = $data; // line 1 holds the titles
            }
        }

        return [$mapped, array_keys(array_filter($unknown, fn ($v, $t) => trim($t) !== '', ARRAY_FILTER_USE_BOTH))];
    }

    /** Check every line without writing: counts, the first lines with what will happen, the errors. */
    public function preview(array $rows): array
    {
        [$mapped, $unknown] = $this->map($rows);
        $result = ['total' => count($mapped), 'create' => 0, 'update' => 0, 'exists' => 0, 'failed' => 0, 'rows' => [], 'errors' => [], 'unknown' => $unknown, 'debit' => 0.0, 'credit' => 0.0, 'value' => 0.0];
        foreach ($mapped as $line => $data) {
            try {
                [$action] = $this->prepare($data);
                $result[$action]++;
                $error = null;
            } catch (\Throwable $e) {
                $result['failed']++;
                $error = $e->getMessage();
                $result['errors'][] = ['line' => $line, 'message' => $error];
                $action = 'failed';
            }
            if (count($result['rows']) < self::PREVIEW_ROWS) {
                $result['rows'][] = ['line' => $line, 'data' => $data, 'action' => $action, 'error' => $error];
            }
        }
        $result['debit'] = round($this->ctx['debit'] ?? 0, 2);
        $result['credit'] = round($this->ctx['credit'] ?? 0, 2);
        $result['value'] = round($this->ctx['value'] ?? 0, 2);
        if ($this->kind === 'opening_balances' && ! $result['failed'] && $result['total']) {
            try {
                $this->checkOpening();
            } catch (\Throwable $e) {
                $result['errors'][] = ['line' => 0, 'message' => $e->getMessage()];
            }
        }

        return $result;
    }

    /** Write the lines; each line is saved whole or not at all. */
    public function run(array $rows, DataImportRun $run): DataImportRun
    {
        @set_time_limit(0);
        $this->ctx['run'] = $run;
        [$mapped] = $this->map($rows);
        $counts = ['created' => 0, 'updated' => 0, 'existing' => 0, 'failed' => 0];
        $errors = [];
        $lines = [];
        foreach ($mapped as $line => $data) {
            try {
                [$action, $apply] = $this->prepare($data);
                if ($action === 'exists') {
                    $counts['existing']++;
                } elseif ($this->kind === 'opening_balances') {
                    $lines[] = $apply;
                } else {
                    DB::transaction($apply);
                    $counts[$action === 'update' ? 'updated' : 'created']++;
                }
            } catch (\Throwable $e) {
                $counts['failed']++;
                $errors[] = ['line' => $line, 'message' => $e->getMessage()];
            }
        }
        if ($this->kind === 'opening_balances') {
            // One balanced entry: every line must be right, or nothing is posted.
            try {
                if ($errors) {
                    throw new \RuntimeException(__('messages.data_import.balances_not_posted'));
                }
                $this->checkOpening();
                $entry = Ledger::post($this->journal(), $this->openingDate(), __('messages.data_import.opening_label'), $lines, null,
                    ['type' => $run->getMorphClass(), 'id' => $run->id, 'event' => 'opening']);
                $counts['created'] = count($lines);
                $run->note = $entry->number;
            } catch (\Throwable $e) {
                $counts['failed'] = max(1, $counts['failed']);
                $counts['created'] = 0;
                $errors[] = ['line' => 0, 'message' => $e->getMessage()];
            }
        }
        if ($this->kind === 'opening_stock') {
            $run->note = __('messages.data_import.stock_value', ['value' => formatAmount($this->ctx['value'] ?? 0)]);
        }
        $run->fill($counts + ['errors' => array_slice($errors, 0, 2000)])->save();

        return $run;
    }

    /** @return array{0: string, 1: \Closure|array|null} "create" with what writes the line, or "exists" */
    private function prepare(array $data): array
    {
        foreach (self::REQUIRED[$this->kind] as $field) {
            if (! isset($data[$field])) {
                $this->fail('missing', ['field' => self::label($field)]);
            }
        }

        return $this->{'prepare'.Str::studly($this->kind)}($data);
    }

    private function yes(?string $value): bool
    {
        return in_array(self::key((string) $value), self::YES, true);
    }

    private function percent(?string $value, string $field): ?float
    {
        $n = $this->number($value === null ? null : rtrim(trim($value), '%'), $field);
        if ($n !== null && ($n < 0 || $n > 100)) {
            $this->fail('bad_percent', ['value' => $value]);
        }

        return $n;
    }

    private function fail(string $key, array $replace = []): never
    {
        throw new \RuntimeException(__('messages.data_import.errors.'.$key, $replace));
    }

    // ---- value helpers

    private function date(?string $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if (is_numeric($value) && (float) $value > 1000) {
                return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString(); // Excel day number
            }
            if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{2,4})$#', $value, $m)) {
                $year = strlen($m[3]) === 2 ? (int) $m[3] + ((int) $m[3] > (int) now()->format('y') ? 1900 : 2000) : (int) $m[3];
                if (! checkdate((int) $m[2], (int) $m[1], $year)) {
                    throw new \InvalidArgumentException();
                }

                return sprintf('%04d-%02d-%02d', $year, $m[2], $m[1]);
            }

            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            $this->fail('bad_date', ['field' => __('messages.data_import.fields.'.$field), 'value' => $value]);
        }
    }

    private function number(?string $value, string $field): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = str_replace([' ', "\u{00A0}", "\u{202F}"], '', $value);
        $clean = str_contains($clean, ',') && str_contains($clean, '.') ? str_replace(',', '', $clean) : str_replace(',', '.', $clean);
        if (! is_numeric($clean)) {
            $this->fail('bad_number', ['field' => __('messages.data_import.fields.'.$field), 'value' => $value]);
        }

        return (float) $clean;
    }

    private function gender(string $value): int
    {
        $k = self::key($value);
        if (in_array($k, ['m', 'h', 'male', 'man', 'homme', 'masculin', 'garcon', '0'], true)) {
            return 0;
        }
        if (in_array($k, ['f', 'female', 'woman', 'femme', 'feminin', 'fille', '1'], true)) {
            return 1;
        }
        $this->fail('bad_gender', ['value' => $value]);
    }

    /** Phone in the form the profile screens store: digits after "+" and the country code. */
    private function phone(?string $value): array
    {
        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '') {
            return [];
        }
        if (str_starts_with(trim((string) $value), '+') || str_starts_with($digits, '00')) {
            return ['phone' => ltrim($digits, '0'), 'prefix_code' => ''];
        }

        return ['phone' => $digits, 'prefix_code' => preg_replace('/\D/', '', (string) ($this->options['prefix'] ?? ''))];
    }

    private function email(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = mb_strtolower($value);
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->fail('bad_email', ['value' => $value]);
        }

        return $value;
    }

    private function listCode(string $type, ?string $value): ?int
    {
        if ($value === null) {
            return null;
        }
        $codes = $this->ctx['list'][$type] ??= MasterListItem::codesByLabel($type);
        $code = $codes[mb_strtolower($value)] ?? (is_numeric($value) && in_array((int) $value, $codes, true) ? (int) $value : null);
        if ($code === null) {
            $this->fail('unknown_item', ['value' => $value, 'list' => __('messages.master_list.types.'.$type)]);
        }

        return $code;
    }

    /** Lower-case name => id of a table, loaded once. */
    private function index(string $name, \Closure $load): array
    {
        return $this->ctx['index'][$name] ??= $load();
    }

    private function seen(string $set, string $key): bool
    {
        return isset($this->ctx['seen'][$set][$key]);
    }

    private function remember(string $set, string $key): void
    {
        $this->ctx['seen'][$set][$key] = true;
    }

    private function userExists(string $email): bool
    {
        $emails = $this->index('emails', fn () => User::whereNotNull('email')->pluck('email')->mapWithKeys(fn ($e) => [mb_strtolower($e) => true])->all());

        return isset($emails[$email]) || $this->seen('email', $email);
    }

    /** Fields shared by the people imports (patients, doctors, staff). */
    private function person(array $data): array
    {
        return array_filter([
            'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'gender' => $this->gender($data['gender']),
            'dob' => $this->date($data['dob'] ?? null, 'dob'), 'email' => $this->email($data['email'] ?? null),
            'designation' => $data['designation'] ?? null, 'qualification' => $data['qualification'] ?? null,
        ], fn ($v) => $v !== null) + $this->phone($data['phone'] ?? null) + ['status' => 1, 'password' => Str::random(40)];
    }

    // ---- one method per kind: check the line, say what will happen, and give what writes it

    private function preparePatients(array $data): array
    {
        $input = $this->person($data) + array_filter(['address1' => $data['address1'] ?? null, 'city' => $data['city'] ?? null,
            'id_document_type' => $this->listCode('id_document_type', $data['id_document_type'] ?? null),
            'id_document_number' => Patient::normalizeIdNumber($data['id_document_number'] ?? null)], fn ($v) => $v !== null);
        $number = isset($data['number']) ? mb_strtoupper($data['number']) : null;
        $numbers = $this->index('patient_numbers', fn () => Patient::pluck('id', 'patient_unique_id')->mapWithKeys(fn ($id, $n) => [mb_strtoupper($n) => $id])->all());
        if ($number !== null) {
            if (isset($numbers[$number]) || $this->seen('patient', $number)) {
                return ['exists', null];
            }
            $this->remember('patient', $number);
        } else {
            // Without the old number, the same name and birth date is taken as the same person.
            $key = self::key($input['last_name'].' '.$input['first_name'].' '.($input['dob'] ?? ''));
            $known = isset($input['dob']) && User::where('owner_type', Patient::class)->where('first_name', $input['first_name'])
                ->where('last_name', $input['last_name'])->whereDate('dob', $input['dob'])->exists();
            if ($known || $this->seen('patient', $key)) {
                return ['exists', null];
            }
            $this->remember('patient', $key);
        }
        if (isset($input['email']) && $this->userExists($input['email'])) {
            unset($input['email']); // the e-mail belongs to someone else: the patient is created without it
        }
        if (isset($input['email'])) {
            $this->remember('email', $input['email']);
        }

        return ['create', function () use ($input, $number) {
            $patient = app(Repositories\PatientRepository::class)->store($input, false);
            if ($number !== null) {
                $patient->update(['patient_unique_id' => $number]); // the old file number stays on the cards
            }
        }];
    }

    private function preparePolicies(array $data): array
    {
        $patientId = Patient::where('patient_unique_id', $data['patient'])->value('id');
        if (! $patientId) {
            $this->fail('unknown_patient', ['value' => $data['patient']]);
        }
        $insurers = $this->index('insurers', fn () => Insurance::get(['id', 'name', 'insurance_code'])
            ->flatMap(fn ($i) => [mb_strtolower($i->name) => $i->id] + ($i->insurance_code ? [mb_strtolower($i->insurance_code) => $i->id] : []))->all());
        $insuranceId = $insurers[mb_strtolower($data['insurer'])] ?? $this->fail('unknown_insurer', ['value' => $data['insurer']]);
        $coverage = $this->number($data['coverage_percent'] ?? null, 'coverage_percent');
        if ($coverage !== null && ($coverage < 0 || $coverage > 100)) {
            $this->fail('bad_percent', ['value' => $data['coverage_percent']]);
        }
        $values = ['patient_id' => $patientId, 'insurance_id' => $insuranceId, 'policy_number' => $data['policy_number'] ?? null,
            'member_number' => $data['member_number'] ?? null, 'plan_name' => $data['plan_name'] ?? null, 'coverage_percent' => $coverage,
            'valid_from' => $this->date($data['valid_from'] ?? null, 'valid_from'), 'valid_to' => $this->date($data['valid_to'] ?? null, 'valid_to')];
        $key = $patientId.'|'.$insuranceId.'|'.mb_strtolower((string) $values['policy_number']);
        if ($this->seen('policy', $key) || PatientInsurance::where('patient_id', $patientId)->where('insurance_id', $insuranceId)
            ->where('policy_number', $values['policy_number'])->exists()) {
            return ['exists', null];
        }
        $this->remember('policy', $key);

        return ['create', fn () => PatientInsurance::create($values + ['is_active' => true,
            'priority' => PatientInsurance::where('patient_id', $patientId)->count() + 1])];
    }

    private function prepareDoctors(array $data): array
    {
        $input = $this->person($data);
        $departments = $this->index('departments', fn () => DoctorDepartment::pluck('id', 'title')->mapWithKeys(fn ($id, $t) => [mb_strtolower($t) => $id])->all());
        $departmentId = $departments[mb_strtolower($data['department'])] ?? $this->fail('unknown_department', ['value' => $data['department']]);
        if ($this->userExists($input['email'])) {
            return ['exists', null];
        }
        $this->remember('email', $input['email']);
        $input += ['doctor_department_id' => $departmentId, 'specialist' => $data['specialist'] ?? $data['department'], 'description' => '',
            'designation' => $data['designation'] ?? $data['department'], 'qualification' => $data['qualification'] ?? '',
            'appointment_charge' => (string) ($this->number($data['appointment_charge'] ?? null, 'appointment_charge') ?? 0)];

        $plan = null;
        if (isset($data['pay_type'])) {
            $types = [];
            foreach (DoctorFeePlan::PAY_TYPES as $t) {
                $types[self::key($t)] = $t;
                foreach (self::locales() as $locale) {
                    $types[self::key(__('messages.doctor_fees.pay_types.'.$t, [], $locale))] = $t;
                }
            }
            $plan = ['pay_type' => $types[self::key($data['pay_type'])] ?? $this->fail('unknown_pay_type', ['value' => $data['pay_type'],
                'types' => implode(', ', array_map(fn ($t) => __('messages.doctor_fees.pay_types.'.$t), DoctorFeePlan::PAY_TYPES))]),
                'rate' => $this->number($data['pay_rate'] ?? null, 'pay_rate') ?? 0, 'withholding_exempt' => $this->yes($data['withholding_exempt'] ?? null)];
        }

        return ['create', function () use ($input, $plan) {
            app(Repositories\DoctorRepository::class)->store($input, false);
            if ($plan) {
                $doctorId = Doctor::whereHas('doctorUser', fn ($q) => $q->where('email', $input['email']))->value('id');
                DoctorFeePlan::updateOrCreate(['doctor_id' => $doctorId], $plan);
            }
        }];
    }

    private function prepareStaff(array $data): array
    {
        $input = $this->person($data);
        $role = collect(self::ROLES)->search(fn ($r) => in_array(self::key($data['role']), $r[0], true));
        if ($role === false) {
            $this->fail('unknown_role', ['value' => $data['role'], 'roles' => implode(', ', array_map(fn ($r) => $r[0][0], self::ROLES))]);
        }
        if ($this->userExists($input['email'])) {
            return ['exists', null];
        }
        $this->remember('email', $input['email']);
        $input += ['designation' => '', 'qualification' => ''];

        return ['create', fn () => app(self::ROLES[$role][1])->store($input, false)];
    }

    private function prepareInsurers(array $data): array
    {
        $name = $data['name'];
        if ($this->seen('insurer', mb_strtolower($name)) || Insurance::where('name', $name)->exists()) {
            return ['exists', null];
        }
        $this->remember('insurer', mb_strtolower($name));
        $types = MasterListItem::options(Insurance::PAYER_TYPE);
        $coverage = $this->number($data['coverage_percent'] ?? null, 'coverage_percent');
        if ($coverage !== null && ($coverage < 0 || $coverage > 100)) {
            $this->fail('bad_percent', ['value' => $data['coverage_percent']]);
        }
        $values = ['name' => mb_substr($name, 0, 160), 'insurance_code' => $data['code'] ?? Str::upper(Str::limit(Str::slug($name, ''), 20, '')),
            'payer_type' => $this->listCode(Insurance::PAYER_TYPE, $data['payer_type'] ?? null) ?? array_key_first($types) ?? 1,
            'coverage_percent' => $coverage, 'discount' => $this->percent($data['discount'] ?? null, 'discount'),
            'contact_person' => $data['contact_person'] ?? null, 'phone' => $data['phone'] ?? null,
            'email' => $this->email($data['email'] ?? null), 'address' => $data['address'] ?? null,
            'credit_days' => ($d = $this->number($data['credit_days'] ?? null, 'credit_days')) === null ? null : (int) $d,
            'claim_note' => $data['claim_note'] ?? null, 'billing_option' => Insurance::BILLING_OPTIONS[0],
            'service_tax' => 0, 'hospital_rate' => 0, 'total' => 0, 'status' => 1];

        return ['create', fn () => Insurance::create($values)];
    }

    private function prepareSuppliers(array $data): array
    {
        if ($this->seen('supplier', mb_strtolower($data['name'])) || Supplier::where('name', $data['name'])->exists()) {
            return ['exists', null];
        }
        $this->remember('supplier', mb_strtolower($data['name']));
        $values = ['name' => $data['name'], 'contact_person' => $data['contact_person'] ?? null, 'phone' => $data['phone'] ?? null,
            'email' => $this->email($data['email'] ?? null), 'address' => $data['address'] ?? null, 'tax_number' => $data['tax_number'] ?? null, 'is_active' => true];

        return ['create', fn () => Supplier::create($values)];
    }

    private function prepareDepartments(array $data): array
    {
        if ($this->seen('department', mb_strtolower($data['name'])) || DoctorDepartment::where('title', $data['name'])->exists()) {
            return ['exists', null];
        }
        $this->remember('department', mb_strtolower($data['name']));
        $values = ['title' => $data['name'], 'description' => $data['description'] ?? null, 'discount_percent' => $this->percent($data['discount_percent'] ?? null, 'discount_percent')];
        unset($this->ctx['index']['departments']);

        return ['create', fn () => DoctorDepartment::create($values)];
    }

    /** Price of an act in every price list, with the doctor and assistant shares; a new act (charge) or consultation price is created. */
    private function prepareTariff(array $data): array
    {
        $type = collect(self::TARIFF_TYPES)->search(fn ($words) => in_array(self::key($data['type']), $words, true));
        if ($type === false) {
            $this->fail('unknown_type', ['value' => $data['type'], 'types' => implode(', ', array_map(fn ($w) => $w[1], self::TARIFF_TYPES))]);
        }
        [$class, $nameColumn, $baseColumn] = ItemPrice::ITEM_TYPES[$type];
        $doctorId = null;
        if ($type === 'doctor_opd_charge') {
            $doctorId = Doctor::whereHas('doctorUser', fn ($q) => $q->whereRaw("CONCAT(first_name, ' ', last_name) = ?", [$data['act']])
                ->orWhereRaw("CONCAT(last_name, ' ', first_name) = ?", [$data['act']])->orWhere('email', mb_strtolower($data['act'])))->value('id')
                ?? $this->fail('unknown_doctor', ['value' => $data['act']]);
            $item = DoctorOPDCharge::where('doctor_id', $doctorId)->first();
        } else {
            $item = $class::where($nameColumn, $data['act'])->first();
        }
        $base = $this->number($data['price'] ?? null, 'price');
        $shares = ['doctor_share_percent' => $this->percent($data['doctor_share'] ?? null, 'doctor_share'),
            'assistant_share_percent' => $this->percent($data['assistant_share'] ?? null, 'assistant_share')];
        $prices = [];
        foreach ($data as $field => $value) {
            if (str_starts_with($field, 'list_')) {
                $prices[(int) substr($field, 5)] = $this->number($value, $field);
            }
        }
        $categoryId = null;
        $newCategory = null;
        if (! $item) {
            if ($base === null) {
                $this->fail('missing', ['field' => self::label('price')]);
            }
            if ($type === 'charge') {
                $categoryId = isset($data['category']) ? ChargeCategory::where('name', $data['category'])->value('id') : null;
                if (! $categoryId) {
                    if (! isset($data['category'], $data['charge_type'])) {
                        $this->fail('no_category');
                    }
                    $newCategory = ['name' => $data['category'], 'charge_type' => $this->listCode('charge_type', $data['charge_type'])];
                }
            } elseif ($type !== 'doctor_opd_charge') {
                $this->fail('unknown_act', ['value' => $data['act'], 'type' => $data['type']]);
            }
        }
        $key = $type.'|'.mb_strtolower($data['act']);
        $action = $item || $this->seen('tariff', $key) ? 'update' : 'create';
        $this->remember('tariff', $key);

        return [$action, function () use ($type, $class, $nameColumn, $baseColumn, $data, $item, $doctorId, $base, $shares, $prices, $categoryId, $newCategory) {
            if (! $item && $type === 'doctor_opd_charge') {
                $item = DoctorOPDCharge::where('doctor_id', $doctorId)->first() ?? DoctorOPDCharge::create(['doctor_id' => $doctorId, 'standard_charge' => $base]);
            } elseif (! $item) {
                $item = $class::where($nameColumn, $data['act'])->first();
                if (! $item) {
                    $categoryId ??= ChargeCategory::where('name', $newCategory['name'])->value('id') ?? ChargeCategory::create($newCategory)->id;
                    $item = Charge::create(['code' => mb_substr($data['act'], 0, 160), 'charge_category_id' => $categoryId,
                        'charge_type' => ChargeCategory::whereKey($categoryId)->value('charge_type'), 'standard_charge' => $base]);
                }
            }
            if ($base !== null) {
                $item->forceFill([$baseColumn => $base])->save();
            }
            $default = PriceList::defaultList();
            $shares = array_filter($shares, fn ($v) => $v !== null);
            if ($default && $shares) {
                ItemPrice::updateOrCreate(['priceable_type' => $type, 'priceable_id' => $item->id, 'price_list_id' => $default->id], $shares);
            }
            foreach ($prices as $listId => $price) {
                if ($price !== null) {
                    ItemPrice::updateOrCreate(['priceable_type' => $type, 'priceable_id' => $item->id, 'price_list_id' => $listId], ['price' => $price]);
                }
            }
        }];
    }

    private function prepareSurgicalActs(array $data): array
    {
        $values = [];
        foreach (['k_surgeon', 'k_assistant', 'k_anaesthetist'] as $field) {
            $values[$field] = $this->number($data[$field] ?? null, $field);
            if ($values[$field] !== null && $values[$field] < 0) {
                $this->fail('bad_number', ['field' => self::label($field), 'value' => $data[$field]]);
            }
        }
        $operation = Operation::where('name', $data['name'])->first();
        $action = $operation || $this->seen('surgical', mb_strtolower($data['name'])) ? 'update' : 'create';
        $this->remember('surgical', mb_strtolower($data['name']));

        return [$action, function () use ($data, $values) {
            $categoryId = isset($data['category']) ? (OperationCategory::where('name', $data['category'])->value('id') ?? OperationCategory::create(['name' => $data['category']])->id) : null;
            $operation = Operation::where('name', $data['name'])->first() ?? new Operation(['name' => $data['name']]);
            $operation->fill(array_filter($values, fn ($v) => $v !== null) + array_filter(['operation_category_id' => $categoryId]));
            if (! $operation->operation_category_id) {
                $operation->operation_category_id = OperationCategory::value('id') ?? OperationCategory::create(['name' => __('messages.data_import.general')])->id;
            }
            $operation->save();
        }];
    }

    /** What an insurer pays for a department and/or a charge category. */
    private function prepareCoverageRules(array $data): array
    {
        $insurers = $this->index('insurers', fn () => Insurance::get(['id', 'name', 'insurance_code'])
            ->flatMap(fn ($i) => [mb_strtolower($i->name) => $i->id] + ($i->insurance_code ? [mb_strtolower($i->insurance_code) => $i->id] : []))->all());
        $insuranceId = $insurers[mb_strtolower($data['insurer'])] ?? $this->fail('unknown_insurer', ['value' => $data['insurer']]);
        $departments = $this->index('departments', fn () => DoctorDepartment::pluck('id', 'title')->mapWithKeys(fn ($id, $t) => [mb_strtolower($t) => $id])->all());
        $departmentId = isset($data['department']) ? ($departments[mb_strtolower($data['department'])] ?? $this->fail('unknown_department', ['value' => $data['department']])) : null;
        $categoryId = isset($data['category']) ? (ChargeCategory::where('name', $data['category'])->value('id') ?? $this->fail('unknown_category', ['value' => $data['category']])) : null;
        if (! $departmentId && ! $categoryId) {
            $this->fail('rule_target');
        }
        $values = ['coverage_percent' => $this->percent($data['coverage_percent'] ?? null, 'coverage_percent'), 'not_covered' => $this->yes($data['not_covered'] ?? null),
            'needs_approval' => $this->yes($data['needs_approval'] ?? null), 'max_amount' => $this->number($data['max_amount'] ?? null, 'max_amount')];
        $where = ['insurance_id' => $insuranceId, 'doctor_department_id' => $departmentId, 'charge_category_id' => $categoryId];
        $key = implode('|', $where);
        $action = CoverageRule::where($where)->exists() || $this->seen('rule', $key) ? 'update' : 'create';
        $this->remember('rule', $key);

        return [$action, fn () => CoverageRule::updateOrCreate($where, $values)];
    }

    private function prepareOpeningStock(array $data): array
    {
        $medicine = null;
        if (isset($data['code'])) {
            $medicine = Medicine::where('cis_code', $data['code'])->orWhere('barcode', $data['code'])->first();
        }
        if (! $medicine && isset($data['medicine'])) {
            $medicine = Medicine::where('name', $data['medicine'])->first();
        }
        if (! $medicine) {
            $this->fail('unknown_medicine', ['value' => $data['code'] ?? $data['medicine'] ?? '']);
        }
        $stores = $this->index('stores', fn () => Store::where('is_active', true)->get(['id', 'name', 'code'])
            ->flatMap(fn ($s) => [mb_strtolower($s->name) => $s->id] + ($s->code ? [mb_strtolower($s->code) => $s->id] : []))->all());
        $storeId = isset($data['store']) ? ($stores[mb_strtolower($data['store'])] ?? $this->fail('unknown_store', ['value' => $data['store']]))
            : ((int) ($this->options['store_id'] ?? 0) ?: $this->fail('no_store'));
        $quantity = $this->number($data['quantity'], 'quantity');
        if ($quantity <= 0 || floor($quantity) != $quantity) {
            $this->fail('bad_quantity', ['value' => $data['quantity']]);
        }
        $batch = ['batch_no' => $data['batch_no'] ?? null, 'expiry_date' => $this->date($data['expiry_date'] ?? null, 'expiry_date'),
            'unit_cost' => $this->number($data['unit_cost'] ?? null, 'unit_cost') ?? (float) $medicine->buying_price];
        $key = implode('|', [$medicine->id, $storeId, mb_strtolower((string) $batch['batch_no']), $batch['expiry_date']]);
        $known = StockMovement::where('movement_type', Stock::OPENING)->whereIn('stock_batch_id', StockBatch::where('store_id', $storeId)
            ->where('stockable_type', $medicine->getMorphClass())->where('stockable_id', $medicine->id)
            ->where('batch_no', $batch['batch_no'] ? trim($batch['batch_no']) : null)->where('expiry_date', $batch['expiry_date'])->select('id'))->exists();
        if ($known || $this->seen('stock', $key)) {
            return ['exists', null];
        }
        $this->remember('stock', $key);
        $this->ctx['value'] = ($this->ctx['value'] ?? 0) + $quantity * $batch['unit_cost'];

        return ['create', fn () => Stock::receive($medicine, $storeId, (int) $quantity, $batch, $this->ctx['run'], Stock::OPENING)];
    }

    private function prepareOpeningBalances(array $data): array
    {
        $accounts = $this->index('accounts', fn () => Account::whereNotNull('code')->get(['id', 'code', 'is_postable', 'status'])->keyBy(fn ($a) => mb_strtolower($a->code))->all());
        $account = $accounts[mb_strtolower($data['account'])] ?? $this->fail('unknown_account', ['value' => $data['account']]);
        if (! $account->is_postable || ! $account->status) {
            $this->fail('group_account', ['value' => $data['account']]);
        }
        $debit = $this->number($data['debit'] ?? null, 'debit') ?? 0.0;
        $credit = $this->number($data['credit'] ?? null, 'credit') ?? 0.0;
        if ($debit < 0 || $credit < 0 || ($debit > 0) === ($credit > 0)) {
            $this->fail('one_side');
        }
        $this->ctx['debit'] = ($this->ctx['debit'] ?? 0) + $debit;
        $this->ctx['credit'] = ($this->ctx['credit'] ?? 0) + $credit;

        return ['create', ['account_id' => $account->id, 'label' => $data['label'] ?? null, 'debit' => $debit, 'credit' => $credit]];
    }

    private function journal(): Journal
    {
        return Journal::where('code', $this->options['journal'] ?? 'OD')->where('is_active', true)->first() ?? $this->fail('no_journal');
    }

    private function openingDate(): string
    {
        return $this->date($this->options['date'] ?? null, 'date') ?? $this->fail('no_date');
    }

    /** Totals must balance, and the year must not already hold opening balances that were not reversed. */
    private function checkOpening(): void
    {
        $debit = round($this->ctx['debit'] ?? 0, 2);
        $credit = round($this->ctx['credit'] ?? 0, 2);
        if (abs($debit - $credit) > 0.004) {
            $this->fail('unbalanced', ['debit' => number_format($debit, 2), 'credit' => number_format($credit, 2)]);
        }
        $this->journal();
        $year = Ledger::yearFor(Carbon::parse($this->openingDate()));
        $posted = JournalEntry::where('fiscal_year_id', $year->id)->where('source_event', 'opening')->whereNull('reversal_of')->get()
            ->first(fn ($e) => ! $e->reversedBy());
        if ($posted) {
            $this->fail('already_opened', ['number' => $posted->number]);
        }
    }
}
