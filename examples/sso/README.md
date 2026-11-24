# Contoh SSO BPS

Contoh PHP mandiri untuk SSO BPS dan API Pegawai. Konfigurasinya dibaca dari `.env` di root proyek.

Jangan memindahkan file PHP di folder ini ke `config/`. Laravel mengeksekusi file PHP dalam `config/` saat bootstrap, sehingga contoh mandiri tersebut akan berjalan pada setiap request aplikasi.

Simpan `CLIENT_SECRET` hanya di `.env`; jangan memasukkannya ke `.env.example` atau Git.
