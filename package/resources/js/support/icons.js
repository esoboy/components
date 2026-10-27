/*
 * Icons a component's script draws come from its view: <x-ui::icon> rendered into
 * <template data-icon-template="role">. Every icon in the library has one source, and
 * the view, not the script, picks the icon for a role.
 */

/**
 * The element's own icon templates, as markup by role.
 */
export function readIcons(element) {
    return Object.fromEntries(
        [...element.querySelectorAll(':scope > template[data-icon-template]')].map(template => [template.dataset.iconTemplate, template.innerHTML.trim()]),
    )
}
