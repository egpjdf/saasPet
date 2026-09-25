import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

interface Organization {
    id: string;
    slug: string;
    name: string;
    settings: Record<string, any>;
    status: string;
    trial_ends_at?: string;
}

interface Workspace {
    id: string;
    organization_id: string;
    slug: string;
    name: string;
    settings: Record<string, any>;
    status: string;
}

interface User {
    id: string;
    name: string;
    email: string;
    role: string;
    workspace_id: string;
    organization_id: string;
    two_factor_enabled: boolean;
}

export const useTenantStore = defineStore('tenant', () => {
    const organization = ref<Organization | null>(null);
    const workspace = ref<Workspace | null>(null);
    const user = ref<User | null>(null);
    const isPlatformAdmin = ref(false);

    const currentTenant = computed(() => ({
        organization: organization.value,
        workspace: workspace.value,
        user: user.value,
        isPlatformAdmin: isPlatformAdmin.value,
    }));

    const hasOrganization = computed(() => !!organization.value);
    const hasWorkspace = computed(() => !!workspace.value);

    const setOrganization = (org: Organization | null) => {
        organization.value = org;
    };

    const setWorkspace = (ws: Workspace | null) => {
        workspace.value = ws;
    };

    const setUser = (u: User | null) => {
        user.value = u;
    };

    const setPlatformAdmin = (isAdmin: boolean) => {
        isPlatformAdmin.value = isAdmin;
    };

    const setTenantData = (data: {
        organization?: Organization | null;
        workspace?: Workspace | null;
        user?: User | null;
        isPlatformAdmin?: boolean;
    }) => {
        if (data.organization !== undefined) organization.value = data.organization;
        if (data.workspace !== undefined) workspace.value = data.workspace;
        if (data.user !== undefined) user.value = data.user;
        if (data.isPlatformAdmin !== undefined) isPlatformAdmin.value = data.isPlatformAdmin;
    };

    const clear = () => {
        organization.value = null;
        workspace.value = null;
        user.value = null;
        isPlatformAdmin.value = false;
    };

    return {
        organization,
        workspace,
        user,
        isPlatformAdmin,
        currentTenant,
        hasOrganization,
        hasWorkspace,
        setOrganization,
        setWorkspace,
        setUser,
        setPlatformAdmin,
        setTenantData,
        clear,
    };
});