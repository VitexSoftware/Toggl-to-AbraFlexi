repoversion=$(shell LANG=C aptitude abraflexi-toggl-importer | grep Version: | awk '{print $$2}')
currentversion=$(shell dpkg-parsechangelog --show-field Version)
nextversion=$(shell echo $(repoversion) | perl -ne 'chomp; print join(".", splice(@{[split/\./,$$_]}, 0, -1), map {++$$_} pop @{[split/\./,$$_]}), "\n";')

all:



deb:
	debuild -i -us -uc -b

dimage:
	docker build -t vitexsoftware/abraflexi-toggl-importer .

drun: dimage
	docker run  -dit --name AbraFlexiTogglImporter -p 8080:80 vitexsoftware/abraflexi-toggl-importer

release:
	echo Release v$(nextversion)
	docker build -t vitexsoftware/abraflexi-toggl-importer:$(nextversion) .
	dch -v $(nextversion) `git log -1 --pretty=%B | head -n 1`
	debuild -i -us -uc -b
	git commit -a -m "Release v$(nextversion)"
	git tag -a $(nextversion) -m "version $(nextversion)"
	docker push vitexsoftware/abraflexi-toggl-importer:$(nextversion)
	docker push vitexsoftware/abraflexi-toggl-importer:latest


