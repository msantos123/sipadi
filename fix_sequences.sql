-- Reiniciar secuencias de PostgreSQL para nacionalidades, departamentos y municipios
SELECT setval('nacionalidades_id_seq', (SELECT COALESCE(MAX(id), 1) FROM nacionalidades));
SELECT setval('departamentos_id_seq', (SELECT COALESCE(MAX(id), 1) FROM departamentos));
SELECT setval('municipios_id_seq', (SELECT COALESCE(MAX(id), 1) FROM municipios));
