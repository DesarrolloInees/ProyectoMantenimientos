-- V8: el Excel diario de indisponibles de Prosegur trae los encabezados
--   FECHA ENVIA A INEES | DEVICE | CODIGO | NOMBRE DEL PUNTO | DELEGACION | DIRECCION | TIPO DE MAUQUINA | COMENTARIOS
-- «DEVICE» no estaba entre los sinónimos del ID de máquina (el archivo se rechazaba completo) y «COMENTARIOS»
-- no se tomaba como descripción. Se agregan sin pisar lo que la oficina ya haya configurado en el parámetro.
UPDATE parametros
SET valor = JSON_ARRAY_APPEND(valor, '$.device_id', 'device')
WHERE clave = 'excel_prosegur_columnas' AND JSON_VALID(valor)
  AND JSON_EXISTS(valor, '$.device_id') AND NOT JSON_CONTAINS(valor, '"device"', '$.device_id');

UPDATE parametros
SET valor = JSON_ARRAY_APPEND(valor, '$.descripcion', 'comentarios', '$.descripcion', 'comentario',
                              '$.descripcion', 'observaciones')
WHERE clave = 'excel_prosegur_columnas' AND JSON_VALID(valor)
  AND JSON_EXISTS(valor, '$.descripcion') AND NOT JSON_CONTAINS(valor, '"comentarios"', '$.descripcion');
