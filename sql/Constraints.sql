ALTER TABLE album
ADD CONSTRAINT constraint_albumnaam UNIQUE (naam);

ALTER TABLE auteur
ADD CONSTRAINT constraint_auteurnaam UNIQUE (naam);

ALTER TABLE stuk
ADD CONSTRAINT fk_stukAlbumId
FOREIGN KEY (albumId)
REFERENCES album(id);

ALTER TABLE stukVersie
ADD CONSTRAINT fk_stukVersieStukId
FOREIGN KEY (stukId)
REFERENCES stuk(id);

ALTER TABLE pagina
ADD CONSTRAINT fk_paginaStukVersieId
FOREIGN KEY (stukVersieId)
REFERENCES stukVersie(id);

