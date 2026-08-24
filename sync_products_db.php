<?php
// ============================================================
// SCRIPT SINKRONISASI DATABASE PRODUK & FOTO VARIAN
// Distributor Pelaminan Family
// ============================================================

require_once __DIR__ . '/config/database.php';

echo "<pre>\n";
echo "=== MEMULAI SINKRONISASI PRODUK & FOTO DATABASE ===\n\n";

try {
    // 1. Pastikan Kategori Ada
    $categories = [
        1 => 'Pelaminan',
        2 => 'Gazebo',
        3 => 'Pot Bunga',
        4 => 'Kotak Akas'
    ];
    foreach ($categories as $catId => $catName) {
        $stmt = $pdo->prepare("INSERT INTO categories (id, name) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=?");
        $stmt->execute([$catId, $catName, $catName]);
    }
    echo "✓ Kategori terverifikasi.\n";

    // 2. Daftar Produk Lengkap
    $products = [
        [
            'code' => 'PLM-004',
            'name' => 'Pelaminan Istana',
            'category_id' => 1,
            'image_url' => '02da1c66ee7d045496fe51f1f129f36c.png',
            'price' => 15000000,
            'size' => 'T: 4m x L: 10m',
            'duration' => 4,
            'description' => 'Pelaminan Istana megah bernuansa emas mewah dengan ukiran istana kerajaan, relief mahkota, serta set sofa tahta pengantin.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'pelaminan_istana_cream_gold.png'],
                ['name' => 'Cokelat Emas', 'image_url' => 'pelaminan_istana_cokelat_emas.png']
            ]
        ],
        [
            'code' => 'PLM-006',
            'name' => 'Pelaminan Eropa Motif Tengah Lengkung',
            'category_id' => 1,
            'image_url' => 'pelaminan_eropa_lengkung_base.png',
            'price' => 30000000,
            'size' => 'T: 4m x L: 10m',
            'duration' => 4,
            'description' => 'Pelaminan Eropa mewah dengan ornamen ukiran khas dan motif lengkungan tengah yang anggun, dilengkapi set kursi pengantin & pendamping.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Putih Emas', 'image_url' => 'pelaminan_eropa_lengkung_putih_emas.png'],
                ['name' => 'Biru Muda Emas', 'image_url' => 'pelaminan_eropa_lengkung_biru_emas.png'],
                ['name' => 'Hijau Sage Emas', 'image_url' => 'pelaminan_eropa_lengkung_hijau_emas.png']
            ]
        ],
        [
            'code' => 'PLM-007',
            'name' => 'Pelaminan Modern Minimalis',
            'category_id' => 1,
            'image_url' => 'pelaminan_modern_minimalis_base.png',
            'price' => 25000000,
            'size' => 'T: 3,5m x L: 9m',
            'duration' => 3,
            'description' => 'Pelaminan modern minimalis dengan aksen ukiran kelopak bunga lotus mewah dan kombinasi warna elegan.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'pelaminan_modern_minimalis_cream_gold_v2.png'],
                ['name' => 'Biru Muda Emas', 'image_url' => 'pelaminan_modern_minimalis_biru_emas_v2.png'],
                ['name' => 'Cokelat Emas', 'image_url' => 'pelaminan_modern_minimalis_cokelat_emas_v2.png']
            ]
        ],
        [
            'code' => 'PLM-008',
            'name' => 'Pelaminan Tirai Lengkung',
            'category_id' => 1,
            'image_url' => 'pelaminan_tirai_lengkung_base.jpg',
            'price' => 28500000,
            'size' => 'T: 4m x L: 9m',
            'duration' => 3,
            'description' => 'Pelaminan tirai lengkung mewah berkonsep Eropa klasik dengan detail ukiran halus, balkon mini, serta tirai latar elegan.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Putih Emas', 'image_url' => 'pelaminan_tirai_lengkung_putih_emas.jpg'],
                ['name' => 'Cream Gold', 'image_url' => 'pelaminan_tirai_lengkung_cream_gold.jpg'],
                ['name' => 'Biru Muda Emas', 'image_url' => 'pelaminan_tirai_lengkung_biru_emas.jpg']
            ]
        ],
        [
            'code' => 'PLM-009',
            'name' => 'Pelaminan Menara Kencana',
            'category_id' => 1,
            'image_url' => 'pelaminan_menara_kencana_base.png',
            'price' => 32000000,
            'size' => 'T: 4m x L: 10m',
            'duration' => 3,
            'description' => 'Pelaminan Menara Kencana berkonsep menara istana emas megah dengan ukiran relief presisi dan tahta pengantin mewah.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Putih Emas', 'image_url' => 'pelaminan_menara_kencana_putih_emas.png'],
                ['name' => 'Cream Gold', 'image_url' => 'pelaminan_menara_kencana_cream_gold.png'],
                ['name' => 'Biru Muda Emas', 'image_url' => 'pelaminan_menara_kencana_biru_emas.png']
            ]
        ],
        [
            'code' => 'PLM-010',
            'name' => 'Pelaminan Eropa Pilar Ukir',
            'category_id' => 1,
            'image_url' => 'pelaminan_eropa_pilar_ukir_base.png',
            'price' => 26000000,
            'size' => 'T: 4m x L: 9m',
            'duration' => 4,
            'description' => 'Pelaminan Eropa Pilar Ukir berkonsep istana megah bernuansa Eropa dengan detail pilar relief ukiran emas presisi, ornamen mahkota, cermin oval tengah yang anggun, serta set kursi sofa pengantin dan pendamping berukir emas mewah.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'pelaminan_eropa_pilar_ukir_cream_gold.png'],
                ['name' => 'Biru Muda Emas', 'image_url' => 'pelaminan_eropa_pilar_ukir_biru_emas.png'],
                ['name' => 'Cokelat Emas', 'image_url' => 'pelaminan_eropa_pilar_ukir_cokelat_emas.png']
            ]
        ],
        [
            'code' => 'PLM-011',
            'name' => 'Pelaminan Villa Eropa',
            'category_id' => 1,
            'image_url' => 'pelaminan_villa_eropa_base.png',
            'price' => 28000000,
            'size' => 'T: 4m x L: 10m',
            'duration' => 4,
            'description' => 'Pelaminan Villa Eropa berkonsep arsitektur hunian klasik Eropa dengan fasad jendela kayu elegan, balkon ukir, pilar corinthian presisi, serta set sofa pengantin dan pendamping bergaya modern luxury.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'pelaminan_villa_eropa_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'pelaminan_villa_eropa_biru_muda.png'],
                ['name' => 'Cokelat Emas', 'image_url' => 'pelaminan_villa_eropa_cokelat_emas.png']
            ]
        ],
        [
            'code' => 'PLM-012',
            'name' => 'Pelaminan Kastil Eropa',
            'category_id' => 1,
            'image_url' => 'pelaminan_kastil_eropa_base.png',
            'price' => 35000000,
            'size' => 'T: 4,5m x L: 11m',
            'duration' => 5,
            'description' => 'Pelaminan Kastil Eropa berkonsep istana kerajaan klasik dengan multiple menara kubah megah, relief ukiran gothic emas presisi, jendela kayu kerajaan, serta set kursi tahta pengantin dan pendamping berukir emas mewah.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'pelaminan_kastil_eropa_cream_gold.png'],
                ['name' => 'Biru Muda Emas', 'image_url' => 'pelaminan_kastil_eropa_biru_muda.png'],
                ['name' => 'Cokelat Emas', 'image_url' => 'pelaminan_kastil_eropa_cokelat_emas.png']
            ]
        ],
        [
            'code' => 'PLM-013',
            'name' => 'Pelaminan Istana Corinthian',
            'category_id' => 1,
            'image_url' => 'pelaminan_roman_luxury_base.png',
            'price' => 32000000,
            'size' => 'T: 4m x L: 10m',
            'duration' => 4,
            'description' => 'Pelaminan Istana Corinthian berkonsep kuil megah Romawi Klasik dengan pilar relief corinthian emas ganda, ukiran pediment mahkota bunga yang anggun, ornamen ukiran lengkung filigree emas, serta set sofa tahta pengantin berukir emas mewah.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'pelaminan_roman_luxury_cream_gold.png'],
                ['name' => 'Biru Muda Emas', 'image_url' => 'pelaminan_roman_luxury_biru_muda.png'],
                ['name' => 'Cokelat Emas', 'image_url' => 'pelaminan_roman_luxury_cokelat_emas.png']
            ]
        ],
        // GAZEBO
        [
            'code' => 'GZB-001',
            'name' => 'Gazebo Lingkar Corinthian',
            'category_id' => 2,
            'image_url' => 'gazebo_lingkar_corinthian_base.png',
            'price' => 5000000,
            'size' => 'T: 4m x L: 6m',
            'duration' => 3,
            'description' => 'Gazebo Lingkar Corinthian berkonsep kuil lingkar Eropa klasik dengan 4 pilar corinthian relief kokoh, atap balustrade melingkar presisi, serta panggung bundar berbahan MDF premium.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'gazebo_lingkar_corinthian_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'gazebo_lingkar_corinthian_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'gazebo_lingkar_corinthian_cokelat_klasik.png']
            ]
        ],
        [
            'code' => 'GZB-002',
            'name' => 'Gazebo Balkon Ukir Eropa',
            'category_id' => 2,
            'image_url' => 'gazebo_balkon_ukir_base.png',
            'price' => 4500000,
            'size' => 'T: 4m x L: 6m',
            'duration' => 3,
            'description' => 'Gazebo Balkon Ukir Eropa berkonsep paviliun balkon klasik dengan mahkota ukiran filigree halus, pilar bermotif kisi silang, serta pagar pembatas balustrade elegan berbahan kayu MDF solid.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'gazebo_balkon_ukir_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'gazebo_balkon_ukir_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'gazebo_balkon_ukir_cokelat_klasik.png']
            ]
        ],
        [
            'code' => 'GZB-003',
            'name' => 'Gazebo Hexagonal Kubah Ukir',
            'category_id' => 2,
            'image_url' => 'gazebo_segi6_kubah_ukir_base.png',
            'price' => 5500000,
            'size' => 'T: 4m x L: 6m',
            'duration' => 3,
            'description' => 'Gazebo Hexagonal Kubah Ukir berkonsep paviliun segi enam klasik dengan mahkota kubah bertingkat, relief ukiran floral, serta tiang pilar kokoh berbahan kayu solid premium.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'gazebo_segi6_kubah_ukir_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'gazebo_segi6_kubah_ukir_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'gazebo_segi6_kubah_ukir_cokelat_klasik.png']
            ]
        ],
        [
            'code' => 'GZB-004',
            'name' => 'Gazebo Gerbang Kisi Ukir',
            'category_id' => 2,
            'image_url' => 'gazebo_gerbang_kisi_ukir_base.png',
            'price' => 4800000,
            'size' => 'T: 4m x L: 6m',
            'duration' => 3,
            'description' => 'Gazebo Gerbang Kisi Ukir berkonsep paviliun gerbang klasik dengan kubah archway lengkung kisi silang, pilar relief persegi corinthian kokoh, serta mahkota cornice rata bermotif ukiran halus.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'gazebo_gerbang_kisi_ukir_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'gazebo_gerbang_kisi_ukir_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'gazebo_gerbang_kisi_ukir_cokelat_klasik.png']
            ]
        ],
        // KOTAK AKAS
        [
            'code' => 'KA-003',
            'name' => 'Kotak Akas Motif Kaca',
            'category_id' => 4,
            'image_url' => 'Kotak Akas Motif Kaca.png',
            'price' => 450000,
            'size' => 'T: 90cm x L: 45cm x P: 45cm',
            'duration' => 2,
            'description' => 'Kotak amplop pernikahan bermotif cermin kaca dan ornamen ukiran emas mewah berbahan MDF premium.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Biru', 'image_url' => 'kotak_akas_motif_kaca_biru.png']
            ]
        ],
        [
            'code' => 'KA-004',
            'name' => 'Kotak Akas Pusaka Emas',
            'category_id' => 4,
            'image_url' => '333204b144a702e0f85defae46895cfd.png',
            'price' => 500000,
            'size' => 'T: 90cm x L: 45cm x P: 45cm',
            'duration' => 2,
            'description' => 'Kotak amplop pernikahan berbalut ornamen pusaka ukir emas mewah dengan sentuhan finishing glossy premium.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Biru', 'image_url' => 'kotak_akas_pusaka_emas_biru.png'],
                ['name' => 'Cokelat', 'image_url' => 'kotak_akas_pusaka_emas_cokelat.png']
            ]
        ],
        [
            'code' => 'KA-005',
            'name' => 'Kotak Akas Bintang Ukir',
            'category_id' => 4,
            'image_url' => 'kotak_akas_bintang_ukir_base.png',
            'price' => 500000,
            'size' => 'T: 90cm x L: 45cm x P: 45cm',
            'duration' => 2,
            'description' => 'Kotak Akas Bintang Ukir berkonsep kotak uang/amplop kubah masjid islami bernuansa mewah dengan relief ukiran bintang segi-8, lis bingkai lengkung kerawang, serta bahan kayu MDF premium.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'kotak_akas_bintang_ukir_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'kotak_akas_bintang_ukir_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'kotak_akas_bintang_ukir_cokelat_klasik.png']
            ]
        ],
        [
            'code' => 'KA-006',
            'name' => 'Kotak Akas Kubah Ukir Arabesq',
            'category_id' => 4,
            'image_url' => 'kotak_akas_kubah_arabesq_base.png',
            'price' => 550000,
            'size' => 'T: 95cm x L: 45cm x P: 45cm',
            'duration' => 2,
            'description' => 'Kotak Akas Kubah Ukir Arabesq berkonsep kotak amplop tahta masjid dengan puncak mahkota kubah bawang (onion dome), lis pilar bulat corinthian, serta panel ukiran floral arabesque khas Timur Tengah yang sangat halus.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'kotak_akas_kubah_arabesq_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'kotak_akas_kubah_arabesq_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'kotak_akas_kubah_arabesq_cokelat_klasik.png']
            ]
        ],
        [
            'code' => 'KA-007',
            'name' => 'Kotak Akas Kipas Art Deco',
            'category_id' => 4,
            'image_url' => 'kotak_akas_kipas_artdeco_base.png',
            'price' => 480000,
            'size' => 'T: 70cm x L: 45cm x P: 45cm',
            'duration' => 2,
            'description' => 'Kotak Akas Kipas Art Deco berkonsep kotak amplop ukiran kipas palmet eropa klasik bernuansa modern vintage dengan tinggi presisi 70 cm, lis pilar relief kerang, serta bahan kayu MDF premium.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'kotak_akas_kipas_artdeco_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'kotak_akas_kipas_artdeco_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'kotak_akas_kipas_artdeco_cokelat_klasik.png']
            ]
        ],
        [
            'code' => 'KA-008',
            'name' => 'Kotak Akas Kisi Silang Lotus',
            'category_id' => 4,
            'image_url' => 'kotak_akas_kisi_silang_base.png',
            'price' => 500000,
            'size' => 'T: 90cm x L: 45cm x P: 45cm',
            'duration' => 2,
            'description' => 'Kotak Akas Kisi Silang Lotus berkonsep kotak amplop ukiran anyaman geometris 3D dengan ornamen pusat kelopak bunga lotus murni, lis pilar garis ganda, serta bahan kayu MDF premium.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Biru Muda', 'image_url' => 'kotak_akas_kisi_silang_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'kotak_akas_kisi_silang_cokelat_klasik.png']
            ]
        ],
        // POT BUNGA
        [
            'code' => 'PB-003',
            'name' => 'Pot Bunga Ukir Eropa Klasik',
            'category_id' => 3,
            'image_url' => 'Standing Flower Klasik.png',
            'price' => 750000,
            'size' => 'T: 90cm x L: 40cm',
            'duration' => 2,
            'description' => 'Pot bunga dekorasi pelaminan dengan motif ukiran pilar klasik Eropa yang elegan berbahan kayu solid premium.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Biru', 'image_url' => 'pot_bunga_eropa_klasik_biru.png'],
                ['name' => 'Cokelat', 'image_url' => 'pot_bunga_eropa_klasik_cokelat.png']
            ]
        ],
        [
            'code' => 'PB-004',
            'name' => 'Pot Bunga Kubah Ukir',
            'category_id' => 3,
            'image_url' => 'pot_bunga_kubah_ukir_base.png',
            'price' => 650000,
            'size' => 'T: 95cm x L: 40cm',
            'duration' => 2,
            'description' => 'Pot Bunga Kubah Ukir berkonsep standing pot bunga tahta kubah dengan relief ukiran khas istana, lis pilar bundar, serta dudukan bunga kokoh.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'pot_bunga_kubah_ukir_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'pot_bunga_kubah_ukir_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'pot_bunga_kubah_ukir_cokelat_klasik.png']
            ]
        ],
        [
            'code' => 'PB-005',
            'name' => 'Pot Bunga Ramping Lotus',
            'category_id' => 3,
            'image_url' => 'pot_bunga_ramping_lotus_base.png',
            'price' => 600000,
            'size' => 'T: 90cm x L: 35cm',
            'duration' => 2,
            'description' => 'Pot Bunga Ramping Lotus berkonsep standing vase ramping bergaya modern oriental dengan motif kelopak lotus mekar dan aksen emas elegan.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'pot_bunga_ramping_lotus_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'pot_bunga_ramping_lotus_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'pot_bunga_ramping_lotus_cokelat_klasik.png']
            ]
        ],
        [
            'code' => 'PB-006',
            'name' => 'Pot Bunga Ukir Anggrek',
            'category_id' => 3,
            'image_url' => 'pot_bunga_ukir_anggrek_base.png',
            'price' => 650000,
            'size' => 'T: 90cm x L: 40cm',
            'duration' => 2,
            'description' => 'Pot Bunga Ukir Anggrek berkonsep standing vase mewah dengan ukiran sulur bunga anggrek mekar di sekeliling badan pot dan kaki berkontur indah.',
            'status' => 'Aktif',
            'variants' => [
                ['name' => 'Cream Gold', 'image_url' => 'pot_bunga_ukir_anggrek_cream_gold.png'],
                ['name' => 'Biru Muda', 'image_url' => 'pot_bunga_ukir_anggrek_biru_muda.png'],
                ['name' => 'Cokelat Klasik', 'image_url' => 'pot_bunga_ukir_anggrek_cokelat_klasik.png']
            ]
        ]
    ];

    // 3. Eksekusi Upsert Produk & Sinkronisasi Varian
    $countProd = 0;
    $countVar = 0;

    foreach ($products as $p) {
        // Cek apakah produk dengan nama atau kode ini sudah ada
        $checkStmt = $pdo->prepare("SELECT id FROM products WHERE code = ? OR name = ? LIMIT 1");
        $checkStmt->execute([$p['code'], $p['name']]);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            $upd = $pdo->prepare("
                UPDATE products 
                SET category_id = ?, name = ?, code = ?, image_url = ?, price = ?, size = ?, production_duration = ?, description = ?, status = ?
                WHERE id = ?
            ");
            $upd->execute([
                $p['category_id'], $p['name'], $p['code'], $p['image_url'], $p['price'], $p['size'], $p['duration'], $p['description'], $p['status'], $existingId
            ]);
            $prodId = (int)$existingId;
            echo "✓ Diperbarui: {$p['code']} - {$p['name']} (ID: {$prodId}, Foto: {$p['image_url']})\n";
        } else {
            $ins = $pdo->prepare("
                INSERT INTO products (category_id, name, code, image_url, price, size, production_duration, description, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $p['category_id'], $p['name'], $p['code'], $p['image_url'], $p['price'], $p['size'], $p['duration'], $p['description'], $p['status']
            ]);
            $prodId = (int)$pdo->lastInsertId();
            echo "+ Ditambahkan: {$p['code']} - {$p['name']} (ID: {$prodId}, Foto: {$p['image_url']})\n";
        }
        $countProd++;

        // Hapus dan tambahkan ulang varian untuk produk ini
        $delVar = $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?");
        $delVar->execute([$prodId]);

        if (!empty($p['variants'])) {
            foreach ($p['variants'] as $v) {
                $insVar = $pdo->prepare("INSERT INTO product_variants (product_id, variant_name, image_url) VALUES (?, ?, ?)");
                $insVar->execute([$prodId, $v['name'], $v['image_url']]);
                $countVar++;
            }
        }
    }

    // Bersihkan produk dummy tanpa gambar lama jika ada
    $delDummy = $pdo->prepare("DELETE FROM products WHERE image_url IS NULL OR image_url = '' OR code IN ('PLM-001','PLM-002','PLM-003','PB-001','PB-002','KA-001','KA-002')");
    $delDummy->execute();

    echo "\n=== SINKRONISASI SELESAI DENGAN SUKSES! ===\n";
    echo "Total Produk Aktif Berfoto: {$countProd}\n";
    echo "Total Varian Warna Aktif: {$countVar}\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "</pre>";
}
