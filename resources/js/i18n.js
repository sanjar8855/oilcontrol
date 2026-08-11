import { usePage } from '@inertiajs/vue3';

/**
 * lang/{locale}/app.php dan Inertia orqali kelgan tarjimalarni o'qiydi.
 * Ishlatish: const { t } = useTranslation(); t('nav.dashboard')
 * Docs: docs/strategiya_va_yol_xaritasi.md — Bosqich 4.
 */
export function useTranslation() {
    const page = usePage();

    const t = (key, replacements = {}) => {
        const value = key.split('.').reduce((acc, part) => (acc && acc[part] !== undefined ? acc[part] : undefined), page.props.translations);

        if (value === undefined) {
            return key;
        }

        return Object.entries(replacements).reduce(
            (text, [search, replacement]) => text.replace(`:${search}`, replacement),
            value
        );
    };

    return { t };
}
