-- Migration: add slug column to miembros if not exists
ALTER TABLE miembros
  ADD COLUMN IF NOT EXISTS slug VARCHAR(180) NULL UNIQUE;

-- Note: populate slugs safely using the provided PHP script to ensure uniqueness.
