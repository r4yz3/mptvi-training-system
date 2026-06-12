export type RoleKey =
    | 'admin'
    | 'manager'
    | 'registrar'
    | 'cashier'
    | 'coordinator';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: RoleKey | null;
    roleLabel: string | null;
    initials: string;
    // Internal tool does not use email verification; kept optional for Breeze's
    // Profile partials which reference it.
    email_verified_at?: string | null;
}

export type Capabilities = Record<string, boolean>;

export interface NavModule {
    id: string;
    label: string;
    icon: string;
    group?: string;
    roles: RoleKey[];
}

export interface Flash {
    success?: string | null;
    error?: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: AuthUser;
        can: Capabilities;
    };
    nav: NavModule[];
    badges: Record<string, number>;
    flash: Flash;
};
