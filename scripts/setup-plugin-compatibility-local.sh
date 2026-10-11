#!/usr/bin/env bash
# Optional, pinned manufacturer-source tests in the isolated Docker installation.
set -euo pipefail
cd "$(dirname "$0")/.."
docker start vpm-local-db vpm-local-wp >/dev/null
python3 - <<'PY'
import json,shutil,subprocess,tempfile
from pathlib import Path
root=Path.cwd(); cache=root/'.runtime/compatibility-plugins';cache.mkdir(parents=True,exist_ok=True)
manifest=json.loads((root/'docs/PLUGIN-QUELLSTAENDE.json').read_text())
for item in manifest:
 dest=cache/item['slug']
 if not dest.exists():
  with tempfile.TemporaryDirectory(prefix='vpm-dependency-') as temp:
   subprocess.run(['git','init',temp],check=True)
   subprocess.run(['git','-C',temp,'remote','add','origin',item['repository']],check=True)
   subprocess.run(['git','-C',temp,'fetch','--depth','1','origin',item['commit']],check=True)
   subprocess.run(['git','-C',temp,'checkout','--detach',item['commit']],check=True)
   assert subprocess.check_output(['git','-C',temp,'rev-parse','HEAD'],text=True).strip()==item['commit']
   shutil.copytree(temp,dest,ignore=shutil.ignore_patterns('.git'))
 subprocess.run(['docker','cp',str(dest),'vpm-local-wp:/var/www/html/wp-content/plugins/'],check=True)
(cache/'sources.json').write_text(json.dumps(manifest,indent=2))
PY
if [ ! -f .runtime/composer-2.8.12.phar ]; then
    curl --fail --location --max-time 45 --output .runtime/composer-2.8.12.phar https://github.com/composer/composer/releases/download/2.8.12/composer.phar
fi
printf '%s\n' 'f446ea719708bb85fcbf4ef18def5d0515f1f9b4d703f6d820c9c1656e10a2f2  .runtime/composer-2.8.12.phar' | sha256sum --check --status
docker cp .runtime/composer-2.8.12.phar vpm-local-wp:/tmp/vpm-composer.phar
docker exec -w /var/www/html/wp-content/plugins/seo-by-rank-math vpm-local-wp php /tmp/vpm-composer.phar dump-autoload --no-dev --no-scripts --no-plugins --optimize
for file in plugin-compatibility-local test-provider-cache; do
    docker cp "scripts/$file.php" "vpm-local-wp:/tmp/vpm-$file.php"
done
docker exec vpm-local-wp mkdir -p /var/www/html/wp-content/mu-plugins
docker cp scripts/plugin-compatibility-hooks.php vpm-local-wp:/var/www/html/wp-content/mu-plugins/vpm-compat-hooks.php
docker exec vpm-local-wp sh -c 'chown -R www-data:www-data wp-content/plugins/seo-by-rank-math wp-content/plugins/litespeed-cache wp-content/plugins/complianz-gdpr; find wp-content/plugins/seo-by-rank-math wp-content/plugins/litespeed-cache wp-content/plugins/complianz-gdpr -type d -exec chmod 755 {} +; find wp-content/plugins/seo-by-rank-math wp-content/plugins/litespeed-cache wp-content/plugins/complianz-gdpr -type f -exec chmod 644 {} +; chmod 644 wp-content/mu-plugins/vpm-compat-hooks.php'
docker exec vpm-local-wp php -r 'require "/var/www/html/wp-load.php"; if(wp_parse_url(home_url(),PHP_URL_HOST)!=="127.0.0.1"){throw new Exception("Local only");} require_once ABSPATH."wp-admin/includes/plugin.php";wp_set_current_user(1);foreach(["seo-by-rank-math/rank-math.php","litespeed-cache/litespeed-cache.php","complianz-gdpr/complianz-gpdr.php"] as $plugin){$r=activate_plugin($plugin);if(is_wp_error($r)){throw new Exception($r->get_error_message());}}update_option("rank_math_registration_skip",true);$r=vpm_prepare_rank_math_metadata();if(is_wp_error($r)){throw new Exception($r->get_error_message());}$o=get_option("rank_math_sitemap",[]);$o["pt_page_sitemap"]="on";$o["pt_vpm_provider_sitemap"]="off";update_option("rank_math_sitemap",$o);flush_rewrite_rules();'
python3 scripts/test-plugin-compatibility.py
docker exec -e VPM_LOCAL_SETUP=1 vpm-local-wp php /tmp/vpm-test-provider-cache.php
