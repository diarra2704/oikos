<?php

namespace Database\Seeders;

use App\Models\ParametreValeur;
use Illuminate\Database\Seeder;

class ParametreValeurSeeder extends Seeder
{
    public function run(): void
    {
        $defauts = [
            'statut_spirituel' => [
                ['valeur' => 'NA', 'libelle' => 'Nouveau Arrivant', 'ordre' => 1],
                ['valeur' => 'NC', 'libelle' => 'Nouveau Converti', 'ordre' => 2],
                ['valeur' => 'fidele', 'libelle' => 'Fidèle', 'ordre' => 3],
                ['valeur' => 'STAR', 'libelle' => 'S.T.A.R', 'ordre' => 4],
                ['valeur' => 'faiseur_disciple', 'libelle' => 'Faiseur de Disciples', 'ordre' => 5],
            ],
            'source' => [
                ['valeur' => 'invitation', 'libelle' => 'Invitation', 'ordre' => 1],
                ['valeur' => 'evangelisation', 'libelle' => 'Évangélisation', 'ordre' => 2],
                ['valeur' => 'culte', 'libelle' => 'Venu au culte', 'ordre' => 3],
                ['valeur' => 'autre', 'libelle' => 'Autre', 'ordre' => 4],
            ],
            'situation_personnelle' => [
                ['valeur' => 'marie_sans_enfant', 'libelle' => 'Marié(e) sans enfant', 'ordre' => 1],
                ['valeur' => 'marie_avec_enfant', 'libelle' => 'Marié(e) avec enfant', 'ordre' => 2],
                ['valeur' => 'fiance', 'libelle' => 'Fiancé(e)', 'ordre' => 3],
                ['valeur' => 'celibataire_sans_enfant', 'libelle' => 'Célibataire sans enfant', 'ordre' => 4],
                ['valeur' => 'celibataire_avec_enfant', 'libelle' => 'Célibataire avec enfant', 'ordre' => 5],
                ['valeur' => 'veuf', 'libelle' => 'Veuf / Veuve', 'ordre' => 6],
                ['valeur' => 'divorce', 'libelle' => 'Divorcé(e)', 'ordre' => 7],
            ],
            'niveau_etude' => [
                ['valeur' => 'primaire', 'libelle' => 'Primaire', 'ordre' => 1],
                ['valeur' => 'secondaire', 'libelle' => 'Secondaire', 'ordre' => 2],
                ['valeur' => 'bac', 'libelle' => 'Bac', 'ordre' => 3],
                ['valeur' => 'licence', 'libelle' => 'Licence', 'ordre' => 4],
                ['valeur' => 'master', 'libelle' => 'Master', 'ordre' => 5],
                ['valeur' => 'doctorat', 'libelle' => 'Doctorat', 'ordre' => 6],
                ['valeur' => 'autre', 'libelle' => 'Autre', 'ordre' => 7],
            ],
            'secteur_activite' => [
                ['valeur' => 'sante', 'libelle' => 'Santé', 'ordre' => 1],
                ['valeur' => 'enseignement', 'libelle' => 'Enseignement', 'ordre' => 2],
                ['valeur' => 'commerce', 'libelle' => 'Commerce', 'ordre' => 3],
                ['valeur' => 'administration', 'libelle' => 'Administration', 'ordre' => 4],
                ['valeur' => 'technique', 'libelle' => 'Technique / Ingénierie', 'ordre' => 5],
                ['valeur' => 'artisanat', 'libelle' => 'Artisanat', 'ordre' => 6],
                ['valeur' => 'sans_emploi', 'libelle' => 'Sans emploi', 'ordre' => 7],
                ['valeur' => 'etudiant', 'libelle' => 'Étudiant', 'ordre' => 8],
                ['valeur' => 'retraite', 'libelle' => 'Retraité', 'ordre' => 9],
                ['valeur' => 'banque_finance', 'libelle' => 'Banque / Finance / Assurance', 'ordre' => 10],
                ['valeur' => 'btp_batiment', 'libelle' => 'BTP / Bâtiment', 'ordre' => 11],
                ['valeur' => 'transport_logistique', 'libelle' => 'Transport / Logistique', 'ordre' => 12],
                ['valeur' => 'informatique_telecom', 'libelle' => 'Informatique / Télécom', 'ordre' => 13],
                ['valeur' => 'agriculture_agroalimentaire', 'libelle' => 'Agriculture / Agroalimentaire', 'ordre' => 14],
                ['valeur' => 'restauration_hotellerie', 'libelle' => 'Restauration / Hôtellerie', 'ordre' => 15],
                ['valeur' => 'securite_defense', 'libelle' => 'Sécurité / Défense', 'ordre' => 16],
                ['valeur' => 'juridique', 'libelle' => 'Juridique', 'ordre' => 17],
                ['valeur' => 'immobilier', 'libelle' => 'Immobilier', 'ordre' => 18],
                ['valeur' => 'media_communication', 'libelle' => 'Média / Communication', 'ordre' => 19],
                ['valeur' => 'industrie', 'libelle' => 'Industrie', 'ordre' => 20],
                ['valeur' => 'secteur_informel', 'libelle' => 'Secteur informel', 'ordre' => 21],
                ['valeur' => 'autre', 'libelle' => 'Autre', 'ordre' => 22],
            ],
            'profession' => [
                ['valeur' => 'enseignant', 'libelle' => 'Enseignant', 'ordre' => 1],
                ['valeur' => 'infirmier', 'libelle' => 'Infirmier(ère)', 'ordre' => 2],
                ['valeur' => 'medecin', 'libelle' => 'Médecin', 'ordre' => 3],
                ['valeur' => 'comptable', 'libelle' => 'Comptable', 'ordre' => 4],
                ['valeur' => 'avocat', 'libelle' => 'Avocat(e)', 'ordre' => 5],
                ['valeur' => 'ingenieur', 'libelle' => 'Ingénieur', 'ordre' => 6],
                ['valeur' => 'technicien', 'libelle' => 'Technicien(ne)', 'ordre' => 7],
                ['valeur' => 'administrateur', 'libelle' => 'Administrateur / Cadre admin', 'ordre' => 8],
                ['valeur' => 'commercial', 'libelle' => 'Commercial(e)', 'ordre' => 9],
                ['valeur' => 'vendeur', 'libelle' => 'Vendeur(euse)', 'ordre' => 10],
                ['valeur' => 'secretaire', 'libelle' => 'Secrétaire', 'ordre' => 11],
                ['valeur' => 'assistant', 'libelle' => 'Assistant(e)', 'ordre' => 12],
                ['valeur' => 'informaticien', 'libelle' => 'Informaticien(ne) / Développeur', 'ordre' => 13],
                ['valeur' => 'conducteur', 'libelle' => 'Conducteur / Chauffeur', 'ordre' => 14],
                ['valeur' => 'mecanicien', 'libelle' => 'Mécanicien(ne)', 'ordre' => 15],
                ['valeur' => 'electricien', 'libelle' => 'Électricien(ne)', 'ordre' => 16],
                ['valeur' => 'plombier', 'libelle' => 'Plombier', 'ordre' => 17],
                ['valeur' => 'menuisier', 'libelle' => 'Menuisier / Ébéniste', 'ordre' => 18],
                ['valeur' => 'maçon', 'libelle' => 'Maçon', 'ordre' => 19],
                ['valeur' => 'peintre_batiment', 'libelle' => 'Peintre en bâtiment', 'ordre' => 20],
                ['valeur' => 'coiffeur', 'libelle' => 'Coiffeur(euse)', 'ordre' => 21],
                ['valeur' => 'cuisinier', 'libelle' => 'Cuisinier(ère)', 'ordre' => 22],
                ['valeur' => 'serveur', 'libelle' => 'Serveur(euse)', 'ordre' => 23],
                ['valeur' => 'agent_securite', 'libelle' => 'Agent de sécurité', 'ordre' => 24],
                ['valeur' => 'policier', 'libelle' => 'Policier / Gendarme', 'ordre' => 25],
                ['valeur' => 'militaire', 'libelle' => 'Militaire', 'ordre' => 26],
                ['valeur' => 'banquier', 'libelle' => 'Banquier / Agent bancaire', 'ordre' => 27],
                ['valeur' => 'assureur', 'libelle' => 'Agent d\'assurance', 'ordre' => 28],
                ['valeur' => 'journaliste', 'libelle' => 'Journaliste', 'ordre' => 29],
                ['valeur' => 'communicant', 'libelle' => 'Chargé(e) de communication', 'ordre' => 30],
                ['valeur' => 'agriculteur', 'libelle' => 'Agriculteur(trice)', 'ordre' => 31],
                ['valeur' => 'eleveur', 'libelle' => 'Éleveur', 'ordre' => 32],
                ['valeur' => 'etudiant', 'libelle' => 'Étudiant(e)', 'ordre' => 33],
                ['valeur' => 'retraite', 'libelle' => 'Retraité(e)', 'ordre' => 34],
                ['valeur' => 'sans_emploi', 'libelle' => 'Sans emploi', 'ordre' => 35],
                ['valeur' => 'autre', 'libelle' => 'Autre', 'ordre' => 36],
            ],
            'quartier' => [
                'Ahala', 'Awae', 'Bastos', 'Biteng', 'Biyem-Assi', 'Carriere', 'Cite Verte', 'Dakar', 'Damase', 'Douala',
                'Dragage', 'Efoulan', 'Ekie', 'Ekoumdoum', 'Ekounou', 'Eleveur', 'Elig-Edzoa', 'Elig-Effa', 'Elig-Essono', 'Emana',
                'Emombo', 'Essos', 'Etam-Bafia', 'Etetak', 'Etoa-Meki', 'Etoudi', 'Etoug-Ebe', 'Febe', 'Fouda', 'Fourgerolles',
                'Jean Vespa', 'Kondengui', 'Leboudi', 'Madagascar', 'Manguier', 'Mballa 2', 'Mbankolo', 'Melen', 'Mendong',
                'Messa', 'Messame-Ndongo', 'Messassi', 'Mimboman', 'Minkoameyos', 'Mokolo', 'Mvan', 'Mvog-Ada', 'Mvog-betsi', 'Mvog-Mbi',
                'Mvolye', 'Ngoa-Ekele', 'Ngoulmekong', 'Ngousso', 'Nkoabang', 'Nkolbikok', 'Nkolbisson', 'Nkolbong', 'Nkoleton', 'Nkolfoulou',
                'Nkolmesseng', 'Nkol-Ndongo', 'NkomKana', 'Nkomo', 'Nkozoa', 'Nlongkak', 'Nsam', 'Nsimeyong', 'Obili', 'Obobogo',
                'Odza', 'Olembe', 'Olezoa', 'Omnisports', 'Oyom-Abang', 'Poste Centrale', 'Rue FOE', 'Santa Barbara', 'Simbock', 'Sous Manguier',
                'Titi Garage', 'Tongolo', 'Tsinga', 'Tsinga Village',
            ],
        ];

        foreach ($defauts as $type => $lignes) {
            foreach ($lignes as $index => $l) {
                if (is_string($l)) {
                    $libelle = trim($l);
                    $valeur = \Illuminate\Support\Str::slug($libelle);
                    ParametreValeur::firstOrCreate(
                        ['type' => $type, 'valeur' => $valeur],
                        ['libelle' => $libelle, 'ordre' => $index + 1]
                    );
                } else {
                    ParametreValeur::firstOrCreate(
                        ['type' => $type, 'valeur' => $l['valeur']],
                        ['libelle' => $l['libelle'], 'ordre' => $l['ordre']]
                    );
                }
            }
        }
    }
}
