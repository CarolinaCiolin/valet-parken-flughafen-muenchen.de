"""Create separate, installable WordPress packages without local test secrets."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parent.parent
out = root / 'dist'
out.mkdir(exist_ok=True)
for relative in ['wordpress/themes/vpm-hybrid', 'wordpress/plugins/vpm-providers', 'wordpress/plugins/vpm-content-drafts']:
    source = root / relative
    target = out / (source.name + '.zip')
    with ZipFile(target, 'w', ZIP_DEFLATED) as archive:
        for file in sorted(source.rglob('*')):
            if file.is_file():
                archive.write(file, file.relative_to(source.parent))
    with ZipFile(target) as archive:
        assert archive.testzip() is None
        assert all(name.startswith(source.name + '/') for name in archive.namelist())
        assert not any('.runtime' in name or 'password' in name or 'fixture' in name for name in archive.namelist())
    target.chmod(0o644)
    print(target.name, target.stat().st_size, 'Bytes')

# Explicit allowlist for the handover archive: no runtime files, SSH keys or credentials.
import hashlib
packages = [out / (name + '.zip') for name in ['vpm-hybrid', 'vpm-providers', 'vpm-content-drafts']]
manifest = out / 'SHA256SUMS.txt'
manifest.write_text(''.join(hashlib.sha256(p.read_bytes()).hexdigest() + '  ' + p.name + '\n' for p in packages))
bundle = out / 'vpm-hostinger-uebergabe.zip'
docs = ['HOSTINGER-SCHNELLSTART.md', 'INSTALLATION.md', 'SEO-METADATEN.md', 'SEO-METADATEN.json', 'ALLGEMEINER-PARTNERLINK.txt', 'PRUEFUNG.md', 'DATENSCHUTZ-INVENTAR.md', 'PFLEGE.md', 'LINK-INVENTAR.csv', 'PLUGIN-KOMPATIBILITAET.md', 'PLUGIN-QUELLSTAENDE.json', 'SPEED-UND-VORSCHAU.md','DESIGN-POTENZIAL.md', 'DESIGN-UND-TECHNIK-AUDIT.md', 'SPEED-AUDIT.json', 'RENDERING-KONTRASTE-IMAGIFY.md', 'RENDER-KONTRAST-AUDIT.json', 'IMAGIFY-QUELLSTAND.json', 'RATGEBER.md', 'UPDATE-0.4.0.md', 'ENGLISCH-POLYLANG.md', 'MULTILINGUAL-BROWSER-PRUEFUNG.json', 'MULTILINGUAL-SEO-EDITOR-PRUEFUNG.json', 'TRADETRACKER-CLEANLINKING.md', 'POLYLANG-QUELLSTAND.json', 'UPDATE-0.5.0.md', 'UPDATE-0.5.1.md', 'UPDATE-0.5.2.md', 'UPDATE-0.5.3.md', 'UPDATE-0.5.4.md', 'UPDATE-0.5.5.md', 'COOKIE-BROWSER-DIAGNOSE-PRUEFUNG.json', 'COMPLIANZ-PRUEFUNG.json', 'SCHEMA-KONTEXT-PRUEFUNG.json']
with ZipFile(bundle, 'w', ZIP_DEFLATED) as archive:
    for file in packages + [manifest]:
        archive.write(file, file.name)
    for name in docs:
        archive.write(root / 'docs' / name, name)
    for slug in ['startseite', 'preise', 'sicherheit', 'ueber-uns']:
        for width in [1440, 390]:
            file = out / f'{slug}-{width}.png'
            if file.exists(): archive.write(file, 'Vorschauen/' + file.name)
    for name in ['ablauf-desktop.png', 'sicherheitskarten-desktop.png', 'startseite-mobil-einstieg.png', 'tablet-menue.png', 'ratgeber-entwurf-desktop.png', 'ratgeber-entwurf-mobil.png']:
        file = out / name
        if file.exists(): archive.write(file, 'Vorschauen/' + name)
    file = out / 'anbieter-mit-symbolbildern.png'
    if file.exists(): archive.write(file, 'Vorschauen/' + file.name)
    file = out / 'vpm-klickbare-vorschau.zip'
    if file.exists(): archive.write(file, file.name)
    for name in ['speed-before.json', 'speed-after.json']:
        file = out / name
        if file.exists(): archive.write(file, 'Pruefung/' + name)
    file = out / 'handover-audit.json'
    if file.exists(): archive.write(file, 'Pruefung/' + file.name)
with ZipFile(bundle) as archive:
    assert archive.testzip() is None
bundle.chmod(0o644)
print(bundle.name, bundle.stat().st_size, 'Bytes')
