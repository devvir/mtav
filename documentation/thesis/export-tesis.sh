#!/usr/bin/env bash
#
# Exporta la tesis (Markdown) a un único .docx para subir a Google Docs.
# El índice se genera solo (--toc). Uso:
#
#   ./export-tesis.sh              # genera ../export/mtav-borrador.docx
#   ./export-tesis.sh salida.docx  # genera el archivo indicado
#   ./export-tesis.sh --upload     # genera y sube a Google Drive (requiere
#                                  # rclone configurado; ver REMOTE/FOLDER abajo).
#                                  # La primera subida crea el Google Doc; las
#                                  # siguientes ACTUALIZAN el mismo doc (misma URL).
#
set -euo pipefail

# Destino en Google Drive para --upload (remote de rclone y carpeta).
REMOTE="gdrive"
FOLDER="MTAV-tesis"

# Ubicarse en la carpeta del script para que las rutas relativas funcionen
# sin importar desde dónde se lo invoque.
cd "$(dirname "$0")"

# Orden de los archivos en el documento final.
FILES=(
  parte-0-resumen.md
  parte-1-introduccion.md
  parte-2-descripcion-funcional.md
  parte-3-el-sorteo.md
  parte-4-ingenieria.md
  bibliografia.md
  apendices.md
)

UPLOAD=0
if [ "${1:-}" = "--upload" ]; then
  UPLOAD=1
  shift
fi

OUT="${1:-../export/mtav-borrador.docx}"

mkdir -p "$(dirname "$OUT")"

# 1) Cuerpo: Markdown -> docx (con índice). assets/reference.docx define los
#    estilos del cuerpo y el salto de página antes del índice (TOCHeading).
BODY="$(mktemp --suffix=.docx)"
pandoc "${FILES[@]}" --toc --toc-depth=2 --reference-doc=assets/reference.docx -o "$BODY"

# 2) Carátula: se antepone assets/caratula.docx (portada mantenida a mano; se
#    edita directamente en LibreOffice/Word cuando haga falta). El índice de
#    pandoc queda naturalmente después: carátula -> índice -> cuerpo.
#    Requiere: pip install docxcompose python-docx
if ! python3 -c "import docxcompose, docx" 2>/dev/null; then
  echo "✗ Falta la dependencia para fusionar la carátula." >&2
  echo "  Instalar con:  python3 -m pip install docxcompose python-docx" >&2
  rm -f "$BODY"; exit 1
fi
python3 - "$BODY" "$OUT" <<'PY'
import sys
from docx import Document
from docxcompose.composer import Composer
from docx.oxml.ns import qn

body_path, out_path = sys.argv[1], sys.argv[2]
master = Document("assets/caratula.docx")
body_el = master.element.body

# Fusionar el cuerpo (pandoc) después de la carátula.
Composer(master).append(Document(body_path))

# UNA SOLA SECCIÓN, margen uniforme. La carátula va centrada, así que no depende
# del margen y se ve igual; el cuerpo conserva su ancho revisado (1"). Mantener
# dos secciones con márgenes distintos generaba un salto de sección a nivel de
# párrafo que Word respeta pero Google Docs interpreta MAL al importar (márgenes
# torcidos). Con una sola sección no hay nada que Google Docs pueda romper.
# 1) Borrar cualquier sectPr a nivel de párrafo (los saltos de sección internos).
for p in body_el.findall(qn('w:p')):
    pPr = p.find(qn('w:pPr'))
    if pPr is not None:
        s = pPr.find(qn('w:sectPr'))
        if s is not None:
            pPr.remove(s)
# 2) Sección única final: márgenes 1" (1440 twips) simétricos.
pgMar = body_el.find(qn('w:sectPr')).find(qn('w:pgMar'))
for side in ('top', 'right', 'bottom', 'left'):
    pgMar.set(qn('w:' + side), '1440')

# 3) Saltos de página: el índice queda solo en su hoja (salto antes del "Resumen")
#    y cada apéndice arranca en hoja nueva (salto antes de cada "Apéndice X").
#    Se usa page-break-before sobre el propio encabezado (no párrafos vacíos), así
#    no quedan líneas en blanco sueltas. Se filtra por estilo de encabezado para
#    no confundir con los "#" que aparecen dentro de bloques de código.
for p in master.paragraphs:
    style = p.style.name if p.style is not None else ""
    t = p.text.strip()
    if style.startswith("Heading") and (t == "Resumen" or t.startswith("Apéndice")):
        p.paragraph_format.page_break_before = True

master.save(out_path)
PY
rm -f "$BODY"

echo "✓ Exportado: $OUT"

# Recordatorio: las notas de borrador [NOTA: ...] no deben sobrevivir a la
# versión final. Avisar si quedan.
NOTAS=$(grep -c "\[NOTA:" "${FILES[@]}" | awk -F: '{s+=$2} END {print s}')
if [ "${NOTAS:-0}" -gt 0 ]; then
  echo "⚠  Quedan $NOTAS nota(s) de borrador [NOTA: ...] en el documento."
  echo "   (para la entrega final deben ser 0: grep -rn '\\[NOTA:' *.md)"
fi

# Subida a Google Drive: convierte el .docx a Google Doc. Si el doc ya existe
# en la carpeta (misma base de nombre), se ACTUALIZA en el lugar: mismo ID,
# misma URL, se conserva lo compartido. No crea archivos nuevos por versión.
if [ "$UPLOAD" = "1" ]; then
  if ! command -v rclone >/dev/null; then
    echo "✗ rclone no está instalado. Instalar y configurar: rclone config (remote: $REMOTE)" >&2
    exit 1
  fi
  rclone copy --drive-import-formats docx "$OUT" "$REMOTE:$FOLDER/"
  echo "✓ Subido a Google Drive: $FOLDER/$(basename "${OUT%.docx}") (como Google Doc)"
fi
