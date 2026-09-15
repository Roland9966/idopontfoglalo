USE idopontfoglalo;

INSERT INTO roles (code, name) VALUES
    ('user', 'Foglaló felhasználó'),
    ('provider', 'Időpontgazda'),
    ('admin', 'Adminisztrátor')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO services (name, description, duration_minutes, location, is_active)
SELECT 'Tanári konzultáció', 'Személyes vagy online tanári konzultáció előzetes időpontfoglalással.', 30, 'Iskola – egyeztetett terem', 1
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Tanári konzultáció');

INSERT INTO services (name, description, duration_minutes, location, is_active)
SELECT 'Szakdolgozati egyeztetés', 'A szakdolgozat tartalmi vagy technikai kérdéseinek megbeszélése.', 45, 'Online vagy személyesen', 1
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Szakdolgozati egyeztetés');

INSERT INTO services (name, description, duration_minutes, location, is_active)
SELECT 'Iskolai ügyintézés', 'Rövid, előre egyeztetett iskolai ügyintézési időpont.', 20, 'Titkárság', 1
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Iskolai ügyintézés');

