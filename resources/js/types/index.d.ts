export interface User {
    id: number;
    nom: string;
    prenom: string;
    telephone: string;
    email?: string;
    role: 'admin' | 'superviseur' | 'leader_cellule' | 'faiseur';
    statut_spirituel: 'NA' | 'NC' | 'fidele' | 'STAR' | 'faiseur_disciple';
    fd_id?: number;
    cellule_id?: number;
    actif: boolean;
    full_name?: string;
    membres_affecter_count?: number;
}

export interface FamilleDisciples {
    id: number;
    nom: string;
    description?: string;
    couleur: string;
    superviseur_id?: number;
    superviseur?: User;
    users_count?: number;
    membres_count?: number;
    cellules_count?: number;
}

export interface Cellule {
    id: number;
    nom: string;
    fd_id: number;
    leader_id?: number;
    leader?: User;
    famille_disciples?: FamilleDisciples;
    faiseurs_count?: number;
    membres_count?: number;
}

export interface Membre {
    id: number;
    nom: string;
    prenom: string;
    telephone?: string;
    email?: string;
    statut_spirituel: 'NA' | 'NC' | 'fidele' | 'STAR' | 'faiseur_disciple';
    genre?: 'M' | 'F';
    fd_id?: number;
    cellule_id?: number;
    suivi_par?: number;
    quartier?: string;
    source?: string;
    actif: boolean;
    derniere_presence?: string;
    notes?: string;
    full_name?: string;
    famille_disciples?: FamilleDisciples;
    cellule?: Cellule;
    faiseur?: User;
    invite_par?: User;
    presences?: Presence[];
}

export interface Presence {
    id: number;
    membre_id: number;
    pointe_par: number;
    type_evenement: string;
    date_evenement: string;
    present: boolean;
    remarque?: string;
    membre?: Membre;
}

export interface Rapport {
    id: number;
    auteur_id: number;
    fd_id?: number;
    type: 'hebdomadaire' | 'mensuel';
    periode_debut: string;
    periode_fin: string;
    contenu: {
        ames_presentes?: number[];
        invitations_lancees?: number;
        immersions_realisees?: number;
        difficultes?: string;
        actions_semaine?: string;
        sujets_priere?: string;
    };
    statut: 'brouillon' | 'soumis' | 'valide' | 'rejete';
    valide_par?: number;
    valide_le?: string;
    auteur?: User;
    famille_disciples?: FamilleDisciples;
}

export interface Invitation {
    id: number;
    inviteur_id: number;
    nom_invite: string;
    telephone_invite?: string;
    date_evenement: string;
    est_venu: boolean;
    devenu_membre: boolean;
}

export interface Badge {
    id: number;
    nom: string;
    slug: string;
    description: string;
    icone: string;
    couleur: string;
}

export interface DashboardStats {
    total_fd?: number;
    total_membres?: number;
    total_faiseurs?: number;
    total_na?: number;
    total_nc?: number;
    membres_actifs?: number;
    absents_3_semaines?: number;
    fd_stats?: FamilleDisciples[];
    fd?: FamilleDisciples;
    total_ames?: number;
    ames_actives?: number;
    absents?: number;
    mes_ames?: Membre[];
    faiseurs?: User[];
}

export interface PaginatedData<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface NotificationItem {
    type: string;
    label: string;
    url: string;
    count: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: {
        success?: string;
        error?: string;
    };
    notifications: {
        items: NotificationItem[];
        total: number;
    };
};
