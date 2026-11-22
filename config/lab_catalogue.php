<?php

/*
|--------------------------------------------------------------------------
| Proposed catalogue of common lab and imaging tests
|--------------------------------------------------------------------------
|
| Added only when the admin clicks "Add the proposed catalogue" on the lab worklist;
| names are written in the admin's language and everything stays editable.
| Reference values are usual adult values: the hospital's lab must check them
| against its own analysers and reagents. Prices are 0: set them in Price lists.
|
| Parameter: [name en, name fr, unit, reference range].
*/

return [
    'lab' => [
        ['category' => ['Haematology', 'Hématologie'], 'tests' => [
            ['name' => ['Complete blood count', 'Numération formule sanguine (NFS)'], 'short' => 'CBC', 'type' => 'blood', 'parameters' => [
                ['White blood cells', 'Leucocytes', '10³/µL', '4 - 10'],
                ['Red blood cells', 'Hématies', '10⁶/µL', '4.2 - 5.9'],
                ['Haemoglobin', 'Hémoglobine', 'g/dL', '12 - 17'],
                ['Haematocrit', 'Hématocrite', '%', '37 - 50'],
                ['MCV', 'VGM', 'fL', '80 - 100'],
                ['MCH', 'TCMH', 'pg', '27 - 33'],
                ['MCHC', 'CCMH', 'g/dL', '32 - 36'],
                ['Platelets', 'Plaquettes', '10³/µL', '150 - 400'],
                ['Neutrophils', 'Polynucléaires neutrophiles', '%', '40 - 75'],
                ['Lymphocytes', 'Lymphocytes', '%', '20 - 45'],
            ]],
            ['name' => ['Erythrocyte sedimentation rate', 'Vitesse de sédimentation (VS)'], 'short' => 'ESR', 'type' => 'blood', 'parameters' => [
                ['ESR 1st hour', 'VS 1re heure', 'mm', '0 - 20'],
            ]],
            ['name' => ['Blood group and Rhesus', 'Groupe sanguin et rhésus'], 'short' => 'ABO', 'type' => 'blood', 'parameters' => [
                ['ABO group', 'Groupe ABO', '—', ''],
                ['Rhesus', 'Rhésus', '—', ''],
            ]],
        ]],
        ['category' => ['Biochemistry', 'Biochimie'], 'tests' => [
            ['name' => ['Fasting blood glucose', 'Glycémie à jeun'], 'short' => 'GLU', 'type' => 'blood', 'parameters' => [['Glucose', 'Glycémie', 'g/L', '0.70 - 1.10']]],
            ['name' => ['Urea', 'Urée'], 'short' => 'URE', 'type' => 'blood', 'parameters' => [['Urea', 'Urée', 'g/L', '0.15 - 0.45']]],
            ['name' => ['Creatinine', 'Créatinine'], 'short' => 'CREA', 'type' => 'blood', 'parameters' => [['Creatinine', 'Créatininémie', 'mg/L', '6 - 13']]],
            ['name' => ['Transaminases', 'Transaminases'], 'short' => 'ALT/AST', 'type' => 'blood', 'parameters' => [
                ['ALT', 'ALAT', 'U/L', '0 - 40'],
                ['AST', 'ASAT', 'U/L', '0 - 40'],
            ]],
            ['name' => ['Lipid profile', 'Bilan lipidique'], 'short' => 'LIP', 'type' => 'blood', 'parameters' => [
                ['Total cholesterol', 'Cholestérol total', 'g/L', '< 2.00'],
                ['HDL cholesterol', 'Cholestérol HDL', 'g/L', '> 0.40'],
                ['Triglycerides', 'Triglycérides', 'g/L', '< 1.50'],
            ]],
            ['name' => ['Uric acid', 'Acide urique'], 'short' => 'UA', 'type' => 'blood', 'parameters' => [['Uric acid', 'Uricémie', 'mg/L', '25 - 70']]],
            ['name' => ['C-reactive protein', 'Protéine C réactive (CRP)'], 'short' => 'CRP', 'type' => 'blood', 'parameters' => [['CRP', 'CRP', 'mg/L', '< 6']]],
            ['name' => ['Glycated haemoglobin', 'Hémoglobine glyquée (HbA1c)'], 'short' => 'HbA1c', 'type' => 'blood', 'parameters' => [['HbA1c', 'HbA1c', '%', '4 - 6']]],
        ]],
        ['category' => ['Parasitology', 'Parasitologie'], 'tests' => [
            ['name' => ['Malaria thick and thin smear', 'Goutte épaisse et frottis'], 'short' => 'GE', 'type' => 'blood', 'parameters' => [
                ['Plasmodium', 'Plasmodium', '—', 'Negative'],
                ['Parasite density', 'Densité parasitaire', 'parasites/µL', '0'],
            ]],
            ['name' => ['Malaria rapid test', 'Test de diagnostic rapide du paludisme (TDR)'], 'short' => 'RDT', 'type' => 'blood', 'parameters' => [['Result', 'Résultat', '—', 'Negative']]],
            ['name' => ['Stool examination', 'Examen parasitologique des selles (KAOP)'], 'short' => 'KAOP', 'type' => 'stool', 'parameters' => [['Result', 'Résultat', '—', '']]],
        ]],
        ['category' => ['Serology', 'Sérologie'], 'tests' => [
            ['name' => ['Widal test', 'Sérodiagnostic de Widal et Félix'], 'short' => 'WIDAL', 'type' => 'blood', 'parameters' => [
                ['Salmonella typhi O', 'Salmonella typhi O', '—', '< 1/80'],
                ['Salmonella typhi H', 'Salmonella typhi H', '—', '< 1/80'],
            ]],
            ['name' => ['HIV test', 'Sérologie VIH'], 'short' => 'HIV', 'type' => 'blood', 'parameters' => [['Result', 'Résultat', '—', 'Negative']]],
            ['name' => ['Hepatitis B surface antigen', 'Antigène HBs'], 'short' => 'HBsAg', 'type' => 'blood', 'parameters' => [['Result', 'Résultat', '—', 'Negative']]],
            ['name' => ['Hepatitis C antibodies', 'Sérologie hépatite C'], 'short' => 'HCV', 'type' => 'blood', 'parameters' => [['Result', 'Résultat', '—', 'Negative']]],
            ['name' => ['Syphilis test', 'Sérologie syphilitique (TPHA/VDRL)'], 'short' => 'TPHA', 'type' => 'blood', 'parameters' => [['Result', 'Résultat', '—', 'Negative']]],
            ['name' => ['Pregnancy test', 'Test de grossesse (β-hCG)'], 'short' => 'hCG', 'type' => 'urine', 'parameters' => [['Result', 'Résultat', '—', '']]],
        ]],
        ['category' => ['Urine', 'Urines'], 'tests' => [
            ['name' => ['Urine dipstick', 'Bandelette urinaire'], 'short' => 'BU', 'type' => 'urine', 'parameters' => [
                ['Proteins', 'Protéines', '—', 'Negative'],
                ['Glucose', 'Glucose', '—', 'Negative'],
                ['Leukocytes', 'Leucocytes', '—', 'Negative'],
                ['Nitrites', 'Nitrites', '—', 'Negative'],
                ['Blood', 'Sang', '—', 'Negative'],
            ]],
            ['name' => ['Urine culture', 'Examen cytobactériologique des urines (ECBU)'], 'short' => 'ECBU', 'type' => 'urine', 'parameters' => [
                ['Leukocytes', 'Leucocytes', '/mm³', '< 10'],
                ['Culture', 'Culture', 'UFC/mL', '< 1000'],
            ]],
        ]],
    ],
    // Imaging: [name en, name fr, short name, modality code (Master List imaging_modality), report template lang_key or null]
    'imaging' => [
        ['category' => ['Ultrasound', 'Échographie'], 'tests' => [
            [['Abdominal ultrasound', 'Échographie abdominale'], 'ECHO-ABD', 1, 'messages.imaging.templates.abdominal_ultrasound'],
            [['Pelvic ultrasound', 'Échographie pelvienne'], 'ECHO-PEL', 1, null],
            [['Obstetric ultrasound', 'Échographie obstétricale'], 'ECHO-OBS', 1, null],
        ]],
        ['category' => ['X-ray', 'Radiographie'], 'tests' => [
            [['Chest X-ray', 'Radiographie thoracique'], 'RX-THX', 2, 'messages.imaging.templates.chest_xray'],
            [['Limb X-ray', 'Radiographie des membres'], 'RX-MBR', 2, null],
            [['Spine X-ray', 'Radiographie du rachis'], 'RX-RAC', 2, null],
        ]],
        ['category' => ['CT scan', 'Scanner'], 'tests' => [
            [['Head CT scan', 'Scanner cérébral'], 'TDM-CER', 3, null],
        ]],
        ['category' => ['Cardiology', 'Cardiologie'], 'tests' => [
            [['Electrocardiogram', 'Électrocardiogramme (ECG)'], 'ECG', 6, null],
        ]],
    ],
];
