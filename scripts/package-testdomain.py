"""Explicit allowlisted test installation bundle. Never includes credentials or Munich content."""
from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED
import hashlib,argparse
p=argparse.ArgumentParser();p.add_argument("--source-dir",default="pakete");p.add_argument("--output-dir",default="pakete");args=p.parse_args()
root=Path(__file__).resolve().parents[1]
out=root/args.output_dir/'vpm-testdomain-installation.zip'
files={f'{args.source_dir}/{n}.zip':f'{n}.zip'for n in ['vpm-hybrid','vpm-providers','vpm-booking-core']}
files.update({'docs/TESTDOMAIN-INSTALLATION.md':'ANLEITUNG.md','deployment/testdomain/wp-config-test.example.php':'wp-config-test.example.php','deployment/testdomain/preflight.php':'preflight.php'})
with ZipFile(out,'w',ZIP_DEFLATED)as z:
 for source,name in files.items():z.write(root/source,name)
 z.writestr('SHA256SUMS.txt',''.join(hashlib.sha256((root/source).read_bytes()).hexdigest()+'  '+name+'\n'for source,name in files.items()))
with ZipFile(out)as z:
 assert z.testzip()is None
 assert set(z.namelist())==set(files.values())|{'SHA256SUMS.txt'}
 assert all('content-drafts'not in n and '.runtime'not in n for n in z.namelist())
print(out.relative_to(root),out.stat().st_size,'bytes; allowlist and archive integrity passed')
