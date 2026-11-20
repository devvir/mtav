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
#   ./export-tesis.sh --final      # versión de entrega: sin marcas de revisión
#                                  # ni el registro de cambios (combinable con --upload).
#
# Marcas de revisión para el tutor (assets/revision.lua): [texto]{.nuevo},
# [texto]{.cambio}, [texto]{.quitado}, bloques ::: nuevo, y notas
# [#15: motivo]{.rev}. Salen coloreadas en el borrador, junto con el registro
# de cambios (cambios.md) al principio; --final las quita.
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
  resumen.md
  capitulo-1-introduccion.md
  capitulo-2-descripcion-funcional.md
  capitulo-3-el-sorteo.md
  capitulo-4-ingenieria.md
  capitulo-5-conclusiones.md
  bibliografia.md
  apendices.md
)

UPLOAD=0
FINAL=0
while [ $# -gt 0 ]; do
  case "$1" in
    --upload) UPLOAD=1; shift ;;
    --final)  FINAL=1; shift ;;
    *) break ;;
  esac
done

OUT="${1:-../export/mtav-borrador.docx}"

mkdir -p "$(dirname "$OUT")"

# El borrador abre con el registro de cambios; la versión final no lo lleva.
if [ "$FINAL" = "0" ]; then
  FILES=(cambios.md "${FILES[@]}")
fi

# 1) Cuerpo: Markdown -> docx (con índice). assets/reference.docx define los
#    estilos del cuerpo y el salto de página antes del índice (TOCHeading).
#    numeracion.lua numera capítulos, secciones y subsecciones y resuelve las
#    referencias (@sec-...); revision.lua aplica (o, con --final, quita) las
#    marcas de revisión.
BODY="$(mktemp --suffix=.docx)"
pandoc "${FILES[@]}" --toc --toc-depth=2 --reference-doc=assets/reference.docx \
  --lua-filter=assets/numeracion.lua --lua-filter=assets/revision.lua -M final="$([ "$FINAL" = "1" ] && echo true || echo false)" \
  -o "$BODY"

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
    if style.startswith("Heading") and (t in ("Resumen", "Cambios en esta versión") or t.startswith("Apéndice")):
        p.paragraph_format.page_break_before = True

# 4) Marcas de revisión (revision.lua): el color va como formato directo sobre
#    cada fragmento, porque Google Docs conserva el formato directo al importar
#    pero no siempre el de los estilos de carácter.
from docx.oxml import OxmlElement
from docx.shared import Pt, RGBColor
from docx.text.run import Run

MARKS = {
    #  estilo        fondo     color del texto  tachado  cursiva  tamaño
    "RevNuevo":   ("E2F3E0", None,             False,   False,   None),
    "RevCambio":  ("FFF3C4", None,             False,   False,   None),
    "RevQuitado": ("F9DEDE", "8A3030",         True,    False,   None),
    "RevNota":    ("E6EEF9", "1F4E8C",         False,   True,    9),
}
SHD_SUCCESSORS = ("w:fitText", "w:vertAlign", "w:rtl", "w:cs", "w:em", "w:lang",
                  "w:eastAsianLayout", "w:specVanish", "w:oMath")
style_ids = {s.style_id: s.name for s in master.styles}
for r in body_el.iter(qn("w:r")):
    rStyle = r.find(qn("w:rPr") + "/" + qn("w:rStyle"))
    mark = MARKS.get(style_ids.get(rStyle.get(qn("w:val")), "")) if rStyle is not None else None
    if mark is None:
        continue
    fill, color, strike, italic, size = mark
    font = Run(r, None).font
    if color:
        font.color.rgb = RGBColor.from_string(color)
    if strike:
        font.strike = True
    if italic:
        font.italic = True
    if size:
        font.size = Pt(size)
    rPr = r.get_or_add_rPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:val"), "clear")
    shd.set(qn("w:color"), "auto")
    shd.set(qn("w:fill"), fill)
    rPr.insert_element_before(shd, *SHD_SUCCESSORS)

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
