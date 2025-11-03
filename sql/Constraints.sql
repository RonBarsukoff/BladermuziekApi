ALTER TABLE album
ADD CONSTRAINT constraint_albumnaam UNIQUE (naam);

ALTER TABLE auteur
ADD CONSTRAINT constraint_auteurnaam UNIQUE (naam);

ALTER TABLE stuk
ADD CONSTRAINT fk_stukAlbumId
FOREIGN KEY (albumId)
REFERENCES album(id);


