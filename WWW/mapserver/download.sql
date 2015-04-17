DROP TABLE download;
CREATE TABLE download (
    uuid varchar NOT NULL,
    wkb_geometry geometry NOT NULL,
    feature varchar NOT NULL,
    date timestamp NOT NULL,
    ip inet DEFAULT NULL,
    single boolean NOT NULL DEFAULT TRUE
) WITH OIDS;
ALTER TABLE download ADD PRIMARY KEY (id);
ALTER TABLE download CLUSTER ON download_pkey;
GRANT SELECT ON download TO webuser;
GRANT SELECT, INSERT, UPDATE, DELETE ON download TO martin;
GRANT INSERT ON download TO webuser;
