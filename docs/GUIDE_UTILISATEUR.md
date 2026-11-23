# Guide utilisateur

Ce guide explique le travail de chaque poste, étape par étape. Les noms entre guillemets sont ceux du menu de gauche et des boutons.

Chaque personne ne voit que les menus de son rôle. Si un menu manque, demandez-le à l'administrateur (« Rôles et permissions »).

**Sommaire**

1. Connexion
2. Accueil et caisse
3. Médecin
4. Infirmier (hospitalisation)
5. Laboratoire et imagerie
6. Pharmacie et magasins
7. Facturation tiers payants
8. Comptable
9. Administrateur
10. Questions fréquentes

---

## 1. Connexion

1. Ouvrez l'adresse du système dans le navigateur (Chrome ou Edge).
2. Saisissez votre e-mail et votre mot de passe.
3. La première fois, cliquez sur « Mot de passe oublié » pour choisir votre mot de passe. Vous le recevez par e-mail.
4. Pour changer de langue ou de mot de passe, cliquez sur votre nom en haut à droite.

Fermez votre session (« Déconnexion ») quand vous quittez le poste.

---

## 2. Accueil et caisse

### 2.1 Trouver ou enregistrer un patient (« Bureau d'accueil »)

- **Trouver un patient.** Tapez le nom, le téléphone, le numéro de pièce ou le numéro patient dans la zone de recherche. Vous pouvez aussi scanner le QR code de la carte patient de l'hôpital.
- **Nouveau patient.** Cliquez sur « Nouveau patient ».
  - **Lire la carte d'identité.** Cliquez sur « Lire la carte d'identité ». Présentez le **dos** de la carte devant la webcam, avec les trois lignes `I<BFA…` dans le cadre vert, et ne bougez pas. Le nom, les prénoms, le sexe, la date de naissance et le numéro se remplissent tout seuls. **Vérifiez-les**, surtout les prénoms, avant d'enregistrer. Un message vous prévient si la carte a expiré.
  - **Photo.** Le bouton « Prendre une photo » prend la photo du patient.
  - **Doublon.** Si le système trouve un patient qui ressemble, il l'affiche. Ouvrez sa fiche au lieu d'en créer une deuxième.
- **Assurance du patient.** Dans la fiche patient, onglet « Assurance », ajoutez l'assureur, le numéro de police, le matricule, les dates de validité et le taux.

### 2.2 Envoyer le patient

Depuis le Bureau d'accueil, sur la ligne du patient :

- **« Consultation ».** Choisissez le médecin et le payeur (paiement direct ou son assurance). La grille tarifaire suit le payeur. Si l'assureur demande un bon, saisissez le « N° bon ». Le prix, la part assureur et la part patient s'affichent avant l'enregistrement.
- **« Examens ».** Choisissez les analyses ou les examens d'imagerie et le prescripteur. Ils seront payés à la caisse.
- **« Hospitalisation ».** Choisissez le lit et le payeur.
- **File d'attente.** Le numéro donné au patient correspond à la carte plastique numérotée qu'on lui remet.

### 2.3 Encaisser (« Caisse »)

1. **Ouvrir la session.** En début de journée, cliquez sur « Ouvrir la caisse » et saisissez le fond de caisse.
2. **Encaisser.**
   1. Cherchez le patient. Tout ce qu'il doit s'affiche : consultations, examens, pharmacie, montants refusés par l'assureur, solde antérieur.
   2. Cochez les lignes à payer.
   3. Choisissez le mode de paiement. Pour un chèque, saisissez le numéro et le tireur.
   4. Saisissez le montant reçu. La monnaie à rendre s'affiche.
   5. Validez. Le reçu s'imprime.
3. **Duplicata.** Dans « Reçus », cherchez le numéro et cliquez sur l'icône de copie. Le reçu porte la mention DUPLICATA.
4. **Transfert de fonds.** « Transfert de fonds » envoie de l'argent vers une autre caisse, le coffre ou la banque.
5. **Arrêt de caisse.**
   1. Cliquez sur « Arrêter la caisse » et comptez la caisse **par mode de paiement**. Le montant attendu est affiché à côté. Les espèces sont obligatoires.
   2. Imprimez l'arrêt, signez-le et remettez-le au comptable avec l'argent.
   3. Si l'écart dépasse l'écart toléré, un superviseur doit valider l'arrêt.

### 2.4 Corriger un reçu (superviseur, menu « Reçus »)

On ne supprime jamais un reçu. On le corrige, et chaque correction garde le nom de son auteur.

- **Annuler.** Choisissez un motif. Si l'administrateur l'a demandé, une **deuxième personne** valide l'annulation dans « En attente de votre validation », en haut de la page Reçus.
- **Rembourser.** L'argent sort de votre caisse ouverte.
- **Changer le mode de paiement ou le médecin.** Possible tant que la session est ouverte.
- **Valider les arrêts de caisse.** Les arrêts qui attendent une validation sont aussi en haut de la page Reçus. Personne ne valide son propre arrêt.

### 2.5 Rapports de caisse

- **« Brouillard de caisse ».** Le solde, jour par jour, par caissière ou pour toutes.
- **« État de passage des patients ».** Il existe en version détaillée, générale, tickets modérateurs, paiements sans assurance et par service.

Chaque rapport a un bouton « Imprimer » et un bouton « Excel ».

---

## 3. Médecin

- **Consultations.** Ouvrez « OPD – Sortie du patient », puis la consultation du patient.
  - **Diagnostic.** Saisissez le diagnostic et, si vous le souhaitez, le **code CIM-10** (tapez le code ou le libellé). Il est imprimé sur les factures des assureurs qui le demandent.
  - **Prescription.** Médicaments, doses et durée.
  - **Examens.** Demandez les analyses ou l'imagerie. Le patient les paie à la caisse.
- **Résultats.** Ouvrez l'onglet « Résultats » de la fiche patient. En hospitalisation, c'est l'onglet « Examens » du séjour.
- **Certificats.** Ouvrez « Certificats », choisissez le modèle (certificat médical, arrêt de travail, aptitude, naissance, décès…), complétez et imprimez.
- **Bloc opératoire.** Ouvrez « Planning du bloc » : la salle, l'heure, l'équipe et la check-list. Le système refuse deux interventions en même temps dans la même salle.
- **Honoraires.** Ouvrez « Honoraires médecins » pour voir vos relevés, selon votre mode de rémunération.

---

## 4. Infirmier (hospitalisation)

Ouvrez « IPD - Entrée patient », puis le séjour du patient.

- **Soins infirmiers.** Saisissez une note par garde.
- **Constantes.** Saisissez la tension, la température, le pouls et le poids. Une valeur hors norme est signalée.
- **Entrées et sorties.** Elles se notent dans le même onglet.
- **Administration des médicaments.** Le planning des prises vient de la prescription. Pour chaque prise, cliquez sur « Donné », « Suspendu » ou « Refusé ». Selon le réglage, le médicament sort automatiquement du stock du service.
- **Sortie.** Remplissez le compte rendu de sortie et la check-list. La sortie se fait avec la facture finale.

---

## 5. Laboratoire et imagerie (« Paillasse labo et imagerie »)

1. **Liste de travail.** Elle montre les examens demandés. Selon le réglage, un examen n'apparaît qu'une fois payé.
2. **Prélèvement.** Cliquez sur « Prélever ». Une étiquette avec le numéro d'échantillon et le QR code s'imprime.
3. **Résultats.**
   - **Saisie.** Les valeurs hors norme sont signalées.
   - **Import.** Pour importer le fichier de l'automate (Excel ou CSV), choisissez le modèle d'import.
4. **Validation.** Le biologiste valide, puis le résultat est diffusé : il apparaît dans la fiche patient et, si le réglage le permet, dans l'espace patient.
5. **Imagerie.**
   1. Déposez les images ou le PDF.
   2. Rédigez le compte rendu à partir d'un modèle.
   3. Le radiologue signe.

---

## 6. Pharmacie et magasins

- **Stock.** Le stock est suivi par dépôt et par lot, avec la date de péremption. Le menu « Stock » montre les quantités par dépôt et les lots qui vont périmer.
- **Vente.**
  1. Créez la facture pharmacie : patient ou client de passage, dépôt, médicaments.
  2. Le patient paie à la **caisse**.
  3. Cliquez sur « Délivrer ». Le système prend d'abord le lot qui périme le plus tôt.
- **Réception fournisseur.** Une réception de médicaments fait entrer le stock dans le dépôt choisi, avec le lot, la péremption et le coût.
- **Commandes.** Le rapport de réapprovisionnement propose les quantités à commander. Une commande devient une réception à l'arrivée.
- **Réquisitions et transferts.** Le service demande, la pharmacie approuve et envoie, puis le service réceptionne. Le stock bouge à chaque étape.
- **Retours.** Du service vers la pharmacie, ou de la pharmacie vers le fournisseur.
- **Inventaire.**
  1. Créez une feuille de comptage pour le dépôt.
  2. Saisissez les quantités comptées. En comptage à l'aveugle, la quantité attendue est cachée.
  3. Soumettez, puis faites approuver. Les écarts deviennent des ajustements.
- **Rapports.** Sorties, mouvements, péremptions, valorisation et produits dormants, avec export Excel.

---

## 7. Facturation tiers payants (« Facturation tiers payants »)

- **Factures assureurs.**
  1. Choisissez l'assureur et la période. Le système propose toutes les parts assureur non encore facturées.
  2. Créez la facture. Selon l'assureur, il y a une facture par passage, par séjour ou par mois.
  3. Imprimez-la. Le titre et les colonnes (N° bon, CIM-10, part patient) suivent la fiche de l'assureur.
- **Règlements.**
  1. Saisissez le paiement de l'assureur, total ou partiel.
  2. Pour une ligne refusée, choisissez le motif, puis ce qu'il faut en faire : la refacturer, la passer en perte, ou la reporter au patient, qui la paiera à la caisse.
- **Suivi.** La « Balance âgée » et le « Relevé » sont disponibles pour chaque assureur.
- **Honoraires médecins.**
  1. Calculez le relevé du mois de chaque médecin, selon son mode : part des actes, par patient, par examen, horaire ou mensuel. Les retenues sont déduites.
  2. Validez le relevé, puis marquez-le payé.
- **Commissions apporteurs.** Le rapport est calculé par apporteur. Enregistrez ensuite les paiements.
- **Pro forma.** Les devis ont une date de validité. Un devis peut être transformé en facture.

---

## 8. Comptable (« Comptabilité »)

- **Écritures automatiques.** Les reçus, annulations, factures assureurs, règlements, achats, sorties de stock, honoraires, salaires, dépenses et virements deviennent des écritures.
  - Elles sont passées chaque nuit si le réglage est activé, ou depuis « Schémas d'écritures » avec le bouton de génération.
  - Une écriture n'est jamais passée deux fois.
  - Si un compte manque, l'écriture concernée est signalée et les autres sont passées.
- **Écriture manuelle (OD).** Seules les écritures équilibrées sont acceptées. On ne modifie jamais une écriture : une erreur se corrige par une contre-passation.
- **États.** Grand livre, balance, compte de résultat, bilan, livre de caisse et chiffre d'affaires par spécialité et par médecin. Chaque état a un export Excel.
- **Export vers Sage.**
  1. Réglez le format une fois : colonnes, séparateur, date, décimales, encodage, et lignes détaillées ou cumulées.
  2. Choisissez la période, vérifiez l'aperçu et téléchargez.
  3. Les écritures exportées sont marquées et ne repartent pas au prochain export.
- **Clôture.**
  - **Date de verrouillage.** Aucune écriture n'est acceptée avant cette date.
  - **Exercice.** Un exercice peut être ouvert, clôturé ou verrouillé.

---

## 9. Administrateur

- **« Rôles et permissions ».** Créez les rôles (caissière, laborantin…) et cochez, pour chaque module, les actions : voir, créer, modifier, supprimer, exporter, valider.
- **« Utilisateurs ».** Affectez la caisse de chaque caissière (page « Caisses ») et les dépôts de chaque pharmacien.
- **« Listes de référence ».** Modes de paiement, types de pièces, motifs de remboursement, provenances, apporteurs, constantes, gardes…
- **« Caisses ».**
  - Les caisses, avec un compte comptable par mode de paiement.
  - Le reçu : numéro de départ, format, texte de pied de page, validité du ticket de consultation.
  - Le partage assureur/patient : ordre de la réduction, cumul des réductions, arrondi de la part patient.
  - Les validations : arrêt de caisse, écart toléré, deuxième personne pour annuler un reçu.
- **« Grilles tarifaires » et « Tarification ».** Le prix de chaque acte par grille (comptant, assuré, personnel, affilié, week-end), les parts médecin et assistant, et la valeur du K.
- **Paramètres > Accueil.** Champs obligatoires, contrôle des doublons, webcam, lecteur de carte, payeur proposé en premier, cartes de file d'attente.
- **« Import des données ».**
  1. Téléchargez tous les modèles (ZIP) et remplissez-les dans l'ordre du GUIDE.
  2. Pour chaque fichier, cliquez sur « Vérifier le fichier », lisez les erreurs, puis cliquez sur « Importer ».
  3. Si des lignes sont en erreur, téléchargez-les avec « Lignes en erreur », corrigez-les, puis réimportez le même fichier : une ligne déjà présente n'est jamais créée deux fois.
- **« Contrôle de mise en service ».** Ce qui reste à régler avant de démarrer. Cette page règle aussi les sauvegardes (heure, copies gardées, seconde copie sur un autre disque) et les tâches de nuit. « Sauvegarder maintenant » fait une sauvegarde immédiate.
- **« Journaux d'audit ».** Qui a créé, modifié ou supprimé quoi, et quand.

La procédure d'installation et de démarrage, pour l'informaticien, est dans `docs/GO_LIVE.md`.

---

## 10. Questions fréquentes

**La webcam ne s'ouvre pas.** Le navigateur doit avoir l'autorisation d'utiliser la caméra (icône à gauche de l'adresse), et le site doit être en https.

**Le lecteur de carte ne lit pas.**
- Utilisez le dos de la carte, avec les trois lignes dans le cadre vert.
- Évitez les reflets et rapprochez la carte.
- La première ouverture prend quelques secondes.

**Je ne trouve pas un menu.** Votre rôle ne l'a pas. Demandez à l'administrateur.

**Le montant à payer me paraît faux.** Vérifiez le payeur (assurance valide à la date), la grille tarifaire et le N° bon si l'assureur exige un accord préalable.

**J'ai encaissé par erreur.** Ne refaites pas de reçu. Demandez à un superviseur d'annuler ou de rembourser le reçu avec un motif.
