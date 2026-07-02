-- Migration: add slug column to miembros.
-- Run these statements only if the column/index are missing in production.
ALTER TABLE miembros
  ADD COLUMN slug VARCHAR(180) NULL AFTER nombre_publico;

ALTER TABLE miembros
  ADD UNIQUE KEY slug (slug);

-- The application fills/updates each slug from the member panel and validates uniqueness before saving.
