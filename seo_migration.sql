-- SEO migration for existing databases
ALTER TABLE contenu
ADD COLUMN IF NOT EXISTS meta_title VARCHAR(60);

ALTER TABLE contenu
ADD COLUMN IF NOT EXISTS meta_description VARCHAR(160);

ALTER TABLE contenu
ADD COLUMN IF NOT EXISTS keywords TEXT;

ALTER TABLE contenu
ADD COLUMN IF NOT EXISTS author_name VARCHAR(120);

-- Optional helper index for SEO queries
CREATE INDEX IF NOT EXISTS idx_contenu_updated_at ON contenu (updated_at);
