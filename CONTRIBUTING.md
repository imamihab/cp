# Contribution Guide

## Branch

Project menggunakan tiga jenis branch:

- `main` = versi stabil
- `develop` = integrasi development
- `feature/*` = pekerjaan masing-masing anggota

## Workflow



1. Update branch develop.
2. Membuat feature branch.
3. Mengerjakan fitur.
4. Commit perubahan.
5. Push feature branch.
6. Membuat Pull Request ke develop.
7. Melakukan testing setelah merge.
8. develop dapat di-merge ke main setelah fitur stabil.

## Contoh

```bash
git checkout develop
git pull origin develop

git checkout -b feature/products