from pathlib import Path
import zipfile
root=Path(__file__).resolve().parents[1]
output=root/'dist'/'OpenTicket-CMS-v0.2.0.zip';output.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(output,'w',zipfile.ZIP_DEFLATED) as archive:
 for folder in ['app','assets','storage']:
  for file in (root/folder).rglob('*'):
   if file.is_file() and (folder!='storage' or file.name in ['.htaccess','.gitkeep']):archive.write(file,file.relative_to(root))
 for name in ['index.php','.htaccess','README.md','LICENSE']:
  archive.write(root/name,name)
 for file in (root/'docs').glob('*.md'):archive.write(file,file.relative_to(root))
print(output)
