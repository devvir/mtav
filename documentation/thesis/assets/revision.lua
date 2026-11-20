--[[
Marcas de revisión para el tutor (temporales: --final las quita).

  [texto]{.nuevo}      agregado        fondo verde
  [texto]{.cambio}     reescrito       fondo amarillo
  [texto]{.quitado}    eliminado       fondo rojo, tachado
  [#15: motivo]{.rev}  nota del cambio "[Rev. #15: motivo]", chica y en color

Una nota con identificador, [#15: motivo]{.rev #rev-15}, es el destino del
enlace que le pone el registro de cambios (cambios.md): [#15](#rev-15).

Para párrafos o secciones enteras, un bloque:

  ::: nuevo
  ...
  :::

El color lo pone export-tesis.sh sobre los estilos de carácter Rev* (formato
directo, que Google Docs conserva al importar). Con -M final=true las marcas
desaparecen: lo nuevo y lo reescrito quedan como texto normal, lo eliminado y
las notas se van.
]]

local KINDS = { nuevo = 'RevNuevo', cambio = 'RevCambio', quitado = 'RevQuitado' }
local final = false

local function kind_of(el)
  for _, class in ipairs(el.classes) do
    if KINDS[class] or class == 'rev' then return class end
  end
end

local function styled(style, inlines, id)
  return pandoc.Span(inlines, pandoc.Attr(id or '', {}, { ['custom-style'] = style }))
end

local function note(span)
  if final then return {} end
  local inlines = pandoc.Inlines({ pandoc.Str('[Rev. ') })
  inlines:extend(span.content)
  inlines:insert(pandoc.Str(']'))
  return styled('RevNota', inlines, span.identifier)
end

-- Inside a block, every run takes the kind's style, except the notes, which keep theirs.
local function mark_inlines(inlines, style)
  local out, run = pandoc.Inlines({}), pandoc.Inlines({})
  local function flush()
    if #run > 0 then out:insert(styled(style, run)); run = pandoc.Inlines({}) end
  end
  for _, el in ipairs(inlines) do
    if el.t == 'Span' and kind_of(el) == 'rev' then
      flush(); out:insert(note(el))
    else
      run:insert(el)
    end
  end
  flush()
  return out
end

local function Span(span)
  local kind = kind_of(span)
  if not kind then return nil end
  if kind == 'rev' then return note(span) end
  if final then
    if kind == 'quitado' then return {} end
    return span.content
  end
  return styled(KINDS[kind], span.content)
end

local function Div(div)
  local kind = kind_of(div)
  if not kind or kind == 'rev' then return nil end
  if final then
    if kind == 'quitado' then return {} end
    return div.content
  end
  local style = KINDS[kind]
  local function mark(block)
    block.content = mark_inlines(block.content, style)
    return block
  end
  return pandoc.Div(div.content:walk({ Para = mark, Plain = mark, Header = mark }))
end

return {
  { Meta = function(meta) final = meta.final == true end },
  -- Blocks first, so the notes inside them are still recognizable.
  { Div = Div },
  { Span = Span },
}
