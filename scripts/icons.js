import { readFileSync, writeFileSync } from 'node:fs'

function phpString(value) {
    return "'" + value.replaceAll('\\', '\\\\').replaceAll("'", "\\'") + "'"
}

function svgElement([tag, attributes]) {
    const parts = Object.entries(attributes).map(([name, value]) => {
        const escaped = String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('"', '&quot;')
            .replaceAll('<', '&lt;')
        return name + '="' + escaped + '"'
    })
    return '<' + [tag, ...parts].join(' ') + '/>'
}

export function buildIcons() {
    const nodes = JSON.parse(readFileSync('node_modules/lucide-static/icon-nodes.json', 'utf8'))
    const names = Object.keys(nodes).sort()
    const rows = names.map((name) => {
        const svg = nodes[name].map(svgElement).join('')
        return '    ' + phpString(name) + ' => ' + phpString(svg) + ','
    })
    writeFileSync(
        'resources/icons/catalog.php',
        ['<?php', '', 'return [', ...rows, '];', ''].join('\n'),
    )
    console.log('Built ' + names.length + ' official Lucide icons.')
}
