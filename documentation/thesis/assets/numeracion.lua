-- Numeración de capítulos, secciones y subsecciones, y referencias cruzadas.
--
-- Los encabezados se escriben sin número; este filtro los numera en orden:
--   # Título {#cap-x}     -> "Capítulo 1 — Título"
--   ## Título {#sec-y}    -> "1.2 Título"
--   ### Título {#sec-z}   -> "1.2.3 Título"
-- Un encabezado de nivel 1 marcado {.unnumbered} (Resumen, Bibliografía,
-- Apéndices, el registro de cambios) apaga la numeración hasta el siguiente
-- nivel 1: los apéndices llevan su letra escrita a mano (A, A.1, ...).
--
-- Las referencias se escriben con el identificador del encabezado:
--   "véase la Sección @sec-y"  -> "véase la Sección 1.2"
--   "(§@sec-z)"                -> "(§1.2.3)"
-- Así la numeración nunca queda desfasada del texto. Una referencia a un
-- identificador inexistente corta la exportación.

local numbers = {}

local function label(header, counters)
  if header.level == 1 then
    return tostring(counters[1])
  end
  return table.concat(counters, '.', 1, header.level)
end

local function number_headers(doc)
  local counters = { 0, 0, 0 }
  local numbering = false
  return doc:walk({
    Header = function(h)
      if h.level == 1 then
        numbering = not h.classes:includes('unnumbered')
      end
      if not numbering or h.level > 3 then
        return nil
      end
      counters[h.level] = counters[h.level] + 1
      for l = h.level + 1, 3 do counters[l] = 0 end
      local n = label(h, counters)
      if h.identifier ~= '' then numbers[h.identifier] = n end
      local prefix = h.level == 1 and ('Capítulo ' .. n .. ' — ') or (n .. ' ')
      h.content:insert(1, pandoc.Str(prefix))
      return h
    end,
  })
end

local function resolve(cite)
  if #cite.citations ~= 1 then return nil end
  local id = cite.citations[1].id
  if not (id:match('^sec%-') or id:match('^cap%-')) then return nil end
  local n = numbers[id]
  if n == nil then
    error('referencia a un encabezado inexistente: @' .. id)
  end
  return pandoc.Str(n)
end

function Pandoc(doc)
  doc = number_headers(doc)
  return doc:walk({ Cite = resolve })
end
