# 📋 CATATAN HOSTING — Pelaminan Family
**Tanggal**: 22 Agustus 2026 | **Pukul**: 23:00 WIB  
**Pemilik Proyek**: Zainal Abidin Fikri  

---

## 🖥️ Informasi Hosting

| Item | Detail |
|---|---|
| **Provider Hosting** | ArenHost |
| **cPanel URL** | `mail.tarsius.kencang.com:2083` |
| **cPanel Username** | `pelamina` |
| **Home Directory** | `/home/pelamina/` |
| **Public HTML** | `/home/pelamina/public_html/` |
| **Shared IP Address** | `195.88.211.130` |

---

## 🌐 Informasi Domain

| Item | Detail |
|---|---|
| **Domain** | `pelaminanfamily.my.id` |
| **www** | `www.pelaminanfamily.my.id` |
| **Registrar** | ArenHost |
| **Tgl Registrasi** | Sabtu, 22 Agustus 2026 |
| **Next Due** | Minggu, 22 Agustus 2027 |
| **Auto Renew** | Enabled |
| **Status Saat Ini** | 🟢 **ACTIVE** (Terdaftar Resmi PANDI 23-08-2026) |

---

## 🔴 Masalah: Domain Pending (22 Agustus 2026)

### Kronologi
- **Malam ini (22/8/2026)** domain `pelaminanfamily.my.id` didaftarkan di ArenHost
- **Hasil nslookup** dari semua DNS server (ISP, Google 8.8.8.8, Cloudflare 1.1.1.1) → `Non-existent domain`
- **Penyebab**: Domain `.my.id` dikelola oleh **PANDI** (Pengelola Nama Domain Internet Indonesia). Setelah ArenHost submit registrasi, PANDI perlu memvalidasi & mengaktifkan domain
- **Status di ArenHost Client Area**: 🟡 `Pending`

### Kenapa Berbeda dengan Project Sebelumnya?
- Project `siakad-smknu.my.id` (juga tgl 22/8/2026) sudah Active karena didaftarkan **lebih pagi**
- `pelaminanfamily.my.id` didaftarkan malam hari → antrian PANDI kemungkinan sudah di luar jam kerja

---

## ✅ Status DNS Zone (cPanel — Sudah Benar)

| Type | Name | Value |
|---|---|---|
| **A** | `pelaminanfamily.my.id` | `195.88.211.130` ✅ |
| **CNAME** | `www.pelaminanfamily.my.id` | `pelaminanfamily.my.id` ✅ |
| **CNAME** | `mail.pelaminanfamily.my.id` | `pelaminanfamily.my.id` ✅ |
| **A** | `ftp.pelaminanfamily.my.id` | `195.88.211.130` ✅ |
| **MX** | `pelaminanfamily.my.id` | `pelaminanfamily.my.id` ✅ |

**Nameserver ArenHost:**
`
srv1.arenhost.com
srv2.arenhost.com
slame1.arenhost.com
`

---

## 🛠️ Solusi Sementara (Akses Lokal)

Edit file `C:\Windows\System32\drivers\etc\hosts` (Notepad → Run as Administrator):

`
# Pelaminan Family - Sementara (hapus setelah DNS aktif)
195.88.211.130  pelaminanfamily.my.id
195.88.211.130  www.pelaminanfamily.my.id
`

Setelah edit, jalankan: `ipconfig /flushdns`

> ⚠️ HAPUS baris tersebut setelah domain berubah ke 🟢 Active!

---

## 🔍 Cara Monitoring

`powershell
nslookup pelaminanfamily.my.id 8.8.8.8
`

Link monitoring:
- https://dnschecker.org/#A/pelaminanfamily.my.id
- https://whois.pandi.or.id
- https://arenhost.com/clientarea.php

---

## ⏱️ Estimasi Waktu Aktif

| Tahap | Estimasi |
|---|---|
| Aktivasi PANDI | 2–24 jam setelah registrasi |
| Propagasi DNS global | 1–4 jam setelah aktif |
| **Total** | ~4–28 jam dari waktu registrasi |

**Perkiraan aktif**: Minggu pagi, 23 Agustus 2026

---

## 📝 Checklist Setelah Domain Aktif

- [x] Aktivasi domain resmi PANDI selesai (Status: Active)
- [x] Install SSL Certificate (Let's Encrypt) via cPanel (Status: Secured 🔒)
- [x] Konfigurasi Force HTTPS & Auto-Detect BASE_URL
- [x] Verifikasi https://pelaminanfamily.my.id bisa diakses (HTTP/1.1 200 OK)
- [ ] Test fungsionalitas publik: login, order, payment, dll.
