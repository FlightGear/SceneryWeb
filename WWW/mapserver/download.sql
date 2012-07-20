DROP TABLE download;
CREATE TABLE download (
    uuid varchar NOT NULL,
    ll_geometry geometry NOT NULL,
    ur_geometry geometry NOT NULL,
    pgislayer varchar NOT NULL,
    requestdate timestamp NOT NULL
) WITH OIDS;
ALTER TABLE download ADD PRIMARY KEY (id);
ALTER TABLE download CLUSTER ON download_pkey;
GRANT SELECT ON download TO webuser;
GRANT SELECT, INSERT, UPDATE, DELETE ON download TO martin;
GRANT INSERT ON download TO webuser;
