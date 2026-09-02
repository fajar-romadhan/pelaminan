/**
 * Leaflet Map Integration & Shopee-Style Address Autocomplete
 * Distributor Pelaminan Family — Sumatera Selatan Only
 *
 * LOCAL_LANDMARKS mencakup seluruh kecamatan Provinsi Sumatera Selatan:
 * ✅ Kota Palembang      (18 kecamatan + landmark, mall, perumahan, jalan)
 * ✅ Kota Prabumulih     (6 kecamatan)
 * ✅ Kota Lubuklinggau   (8 kecamatan)
 * ✅ Kota Pagar Alam     (5 kecamatan)
 * ✅ Kab. Banyuasin      (21 kecamatan)
 * ✅ Kab. Musi Banyuasin (14 kecamatan)
 * ✅ Kab. Musi Rawas     (14 kecamatan)
 * ✅ Kab. Muratara       (7 kecamatan)
 * ✅ Kab. Lahat          (24 kecamatan)
 * ✅ Kab. Muara Enim     (22 kecamatan)
 * ✅ Kab. Empat Lawang   (10 kecamatan)
 * ✅ Kab. PALI           (5 kecamatan)
 * ✅ Kab. Ogan Ilir      (16 kecamatan)
 * ✅ Kab. OKI            (18 kecamatan)
 * ✅ Kab. OKU            (13 kecamatan)
 * ✅ Kab. OKU Timur      (20 kecamatan)
 * ✅ Kab. OKU Selatan    (19 kecamatan)
 */

(function () {

  // ============================================================
  // BOUNDING BOX SUMATERA SELATAN
  // ============================================================
  var SUMSEL_BBOX = { minLat: -5.10, maxLat: -1.50, minLng: 101.80, maxLng: 106.20 };

  function isInsideSouthSumatra(lat, lng) {
    return lat >= SUMSEL_BBOX.minLat && lat <= SUMSEL_BBOX.maxLat &&
           lng >= SUMSEL_BBOX.minLng && lng <= SUMSEL_BBOX.maxLng;
  }

  // ============================================================
  // DICTIONARY LENGKAP SUMATERA SELATAN
  // ============================================================
  var LOCAL_LANDMARKS = [
    { title:'Pelaminan Family Zainal (Gudang Utama)', subtitle:'Sematang Borang / Sako, Palembang, Sumatera Selatan 30161', lat:-2.9389551, lng:104.8106462, keywords:["pelaminan family", "pelaminan family zainal", "gudang pelaminan", "toko pelaminan", "zainal abidin fikri"] },
    { title:'Perumahan Musi Palem Indah', subtitle:'Sungai Pinang / Sungai Kedukan, Rambutan, Banyuasin 30762', lat:-3.040798, lng:104.838521, keywords:["musi palem indah", "perumahan musi palem", "palem indah", "musi palem"] },
    { title:'Perumahan Musi Palem Indah Blok A', subtitle:'Sungai Pinang, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.0402, lng:104.838, keywords:["musi palem indah blok a", "palem indah blok a"] },
    { title:'Perumahan Musi Palem Indah Blok B', subtitle:'Sungai Pinang, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.0405, lng:104.8383, keywords:["musi palem indah blok b", "palem indah blok b"] },
    { title:'Perumahan Musi Palem Indah Blok C', subtitle:'Sungai Pinang, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.0409, lng:104.8387, keywords:["musi palem indah blok c", "palem indah blok c"] },
    { title:'Perumahan Musi Palem Indah Blok D', subtitle:'Sungai Pinang, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.0412, lng:104.839, keywords:["musi palem indah blok d", "palem indah blok d"] },
    { title:'Masjid Al-Ikhlas Musi Palem Indah', subtitle:'Perumahan Musi Palem Indah, Rambutan, Banyuasin', lat:-3.0407, lng:104.8384, keywords:["masjid musi palem", "masjid palem indah"] },
    { title:'Jl. Musi Palem Indah', subtitle:'Sungai Pinang, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.0406, lng:104.8378, keywords:["jalan musi palem", "jl musi palem"] },
    { title:'Desa Sungai Pinang (Rambutan)', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.042, lng:104.84, keywords:["sungai pinang rambutan", "desa sungai pinang"] },
    { title:'Perumahan Sungai Pinang Indah', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.044, lng:104.842, keywords:["sungai pinang indah", "perumahan sungai pinang"] },
    { title:'Perumahan Griya Sungai Pinang', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.046, lng:104.845, keywords:["griya sungai pinang"] },
    { title:'Perumahan Rambutan Asri', subtitle:'Sungai Kedukan / Rambutan, Banyuasin', lat:-3.05, lng:104.835, keywords:["rambutan asri", "perumahan rambutan asri"] },
    { title:'Perumahan Graha Sungai Kedukan', subtitle:'Sungai Kedukan, Rambutan, Banyuasin', lat:-3.052, lng:104.83, keywords:["graha sungai kedukan"] },
    { title:'Perumahan Plaju Garden', subtitle:'Plaju Darat / Rambutan, Banyuasin', lat:-3.035, lng:104.832, keywords:["plaju garden", "perumahan plaju garden"] },
    { title:'Pasar Tradisional Sungai Pinang', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.043, lng:104.841, keywords:["pasar sungai pinang"] },
    { title:'Puskesmas Pembantu Rambutan / Sungai Pinang', subtitle:'Rambutan, Banyuasin', lat:-3.041, lng:104.843, keywords:["puskesmas sungai pinang", "puskesmas rambutan"] },
    { title:'Pulau Layang', subtitle:'Pulau Layang, Rambutan/Banyuasin/Palembang', lat:-3.016667, lng:104.833333, keywords:['pulau layang', 'island'] },
    { title:'Siju', subtitle:'Siju, Rambutan/Banyuasin/Palembang', lat:-3.066241, lng:105.000267, keywords:['siju', 'village'] },
    { title:'Palu', subtitle:'Palu, Rambutan/Banyuasin/Palembang', lat:-3.162576, lng:104.791992, keywords:['palu', 'village'] },
    { title:'Kertapati', subtitle:'Kertapati, Rambutan/Banyuasin/Palembang', lat:-3.022472, lng:104.750311, keywords:['kertapati', 'village'] },
    { title:'Sei Selincah', subtitle:'Sei Selincah, Rambutan/Banyuasin/Palembang', lat:-2.967581, lng:104.823349, keywords:['sei selincah', 'village'] },
    { title:'Sungai Rebo', subtitle:'Sungai Rebo, Rambutan/Banyuasin/Palembang', lat:-3.003565, lng:104.844048, keywords:['sungai rebo', 'village'] },
    { title:'Ulak Tembaga', subtitle:'Ulak Tembaga, Rambutan/Banyuasin/Palembang', lat:-3.144136, lng:104.860846, keywords:['ulak tembaga', 'village'] },
    { title:'5 Ulu', subtitle:'5 Ulu, Rambutan/Banyuasin/Palembang', lat:-3.001921, lng:104.763339, keywords:['5 ulu', 'village'] },
    { title:'Kebon Sahang', subtitle:'Kebon Sahang, Rambutan/Banyuasin/Palembang', lat:-3.117134, lng:104.999953, keywords:['kebon sahang', 'village'] },
    { title:'Pemulutan Ulu', subtitle:'Pemulutan Ulu, Rambutan/Banyuasin/Palembang', lat:-3.130392, lng:104.805341, keywords:['pemulutan ulu', 'village'] },
    { title:'Sukadarma', subtitle:'Sukadarma, Rambutan/Banyuasin/Palembang', lat:-3.130442, lng:104.885834, keywords:['sukadarma', 'village'] },
    { title:'Duren Ijo', subtitle:'Duren Ijo, Rambutan/Banyuasin/Palembang', lat:-2.978608, lng:104.941784, keywords:['duren ijo', 'village'] },
    { title:'Suka Mulya', subtitle:'Suka Mulya, Rambutan/Banyuasin/Palembang', lat:-2.96268, lng:104.816056, keywords:['suka mulya', 'village'] },
    { title:'9/10 Ulu', subtitle:'9/10 Ulu, Rambutan/Banyuasin/Palembang', lat:-2.995073, lng:104.770881, keywords:['9/10 ulu', 'village'] },
    { title:'Tanjung Ali', subtitle:'Tanjung Ali, Rambutan/Banyuasin/Palembang', lat:-3.120822, lng:104.855912, keywords:['tanjung ali', 'village'] },
    { title:'Ibul Besar', subtitle:'Ibul Besar, Rambutan/Banyuasin/Palembang', lat:-3.088397, lng:104.77959, keywords:['ibul besar', 'village'] },
    { title:'14 Ulu', subtitle:'14 Ulu, Rambutan/Banyuasin/Palembang', lat:-2.987201, lng:104.781592, keywords:['14 ulu', 'village'] },
    { title:'Durian Gadis', subtitle:'Durian Gadis, Rambutan/Banyuasin/Palembang', lat:-3.028471, lng:104.949484, keywords:['durian gadis', 'village'] },
    { title:'Perajen', subtitle:'Perajen, Rambutan/Banyuasin/Palembang', lat:-2.943855, lng:104.892458, keywords:['perajen', 'village'] },
    { title:'Desa Baru', subtitle:'Desa Baru, Rambutan/Banyuasin/Palembang', lat:-3.002366, lng:104.978481, keywords:['desa baru', 'village'] },
    { title:'Pedu', subtitle:'Pedu, Rambutan/Banyuasin/Palembang', lat:-3.120692, lng:104.834723, keywords:['pedu', 'village'] },
    { title:'Pegayut', subtitle:'Pegayut, Rambutan/Banyuasin/Palembang', lat:-3.07659, lng:104.801971, keywords:['pegayut', 'village'] },
    { title:'Sungai Gerong', subtitle:'Sungai Gerong, Rambutan/Banyuasin/Palembang', lat:-2.990391, lng:104.862979, keywords:['sungai gerong', 'village'] },
    { title:'1 Ulu', subtitle:'1 Ulu, Rambutan/Banyuasin/Palembang', lat:-3.013218, lng:104.752987, keywords:['1 ulu', 'village'] },
    { title:'Jakabaring', subtitle:'Jakabaring, Rambutan/Banyuasin/Palembang', lat:-3.020743, lng:104.783453, keywords:['jakabaring', 'village'] },
    { title:'Sei Lais', subtitle:'Sei Lais, Rambutan/Banyuasin/Palembang', lat:-2.979466, lng:104.835667, keywords:['sei lais', 'village'] },
    { title:'Tanjung Kerang', subtitle:'Tanjung Kerang, Rambutan/Banyuasin/Palembang', lat:-3.046921, lng:104.931621, keywords:['tanjung kerang', 'village'] },
    { title:'Pelaju', subtitle:'Pelaju, Rambutan/Banyuasin/Palembang', lat:-3.056266, lng:104.98182, keywords:['pelaju', 'village'] },
    { title:'Parit', subtitle:'Parit, Rambutan/Banyuasin/Palembang', lat:-3.062408, lng:104.953527, keywords:['parit', 'village'] },
    { title:'15 Ulu', subtitle:'15 Ulu, Rambutan/Banyuasin/Palembang', lat:-3.049521, lng:104.769246, keywords:['15 ulu', 'village'] },
    { title:'Pelabuhan Dalam', subtitle:'Pelabuhan Dalam, Rambutan/Banyuasin/Palembang', lat:-3.115641, lng:104.757298, keywords:['pelabuhan dalam', 'village'] },
    { title:'Tanjung Merbu', subtitle:'Tanjung Merbu, Rambutan/Banyuasin/Palembang', lat:-3.085735, lng:104.901665, keywords:['tanjung merbu', 'village'] },
    { title:'3-4 Ulu', subtitle:'3-4 Ulu, Rambutan/Banyuasin/Palembang', lat:-3.005895, lng:104.758004, keywords:['3-4 ulu', 'village'] },
    { title:'11 Ulu', subtitle:'11 Ulu, Rambutan/Banyuasin/Palembang', lat:-2.990556, lng:104.768179, keywords:['11 ulu', 'village'] },
    { title:'Lingkis', subtitle:'Lingkis, Rambutan/Banyuasin/Palembang', lat:-3.173887, lng:104.872958, keywords:['lingkis', 'village'] },
    { title:'Sako', subtitle:'Sako, Rambutan/Banyuasin/Palembang', lat:-3.075037, lng:104.876111, keywords:['sako', 'village'] },
    { title:'Sei Selayur', subtitle:'Sei Selayur, Rambutan/Banyuasin/Palembang', lat:-2.966579, lng:104.804524, keywords:['sei selayur', 'village'] },
    { title:'Rambutan', subtitle:'Rambutan, Rambutan/Banyuasin/Palembang', lat:-3.129228, lng:104.932642, keywords:['rambutan', 'village'] },
    { title:'Ogan Baru', subtitle:'Ogan Baru, Rambutan/Banyuasin/Palembang', lat:-3.023814, lng:104.755088, keywords:['ogan baru', 'village'] },
    { title:'Kemas Rindo', subtitle:'Kemas Rindo, Rambutan/Banyuasin/Palembang', lat:-3.032644, lng:104.752953, keywords:['kemas rindo', 'village'] },
    { title:'Muara Batun', subtitle:'Muara Batun, Rambutan/Banyuasin/Palembang', lat:-3.184086, lng:104.84689, keywords:['muara batun', 'village'] },
    { title:'Sungai Rasau', subtitle:'Sungai Rasau, Rambutan/Banyuasin/Palembang', lat:-3.088948, lng:104.826733, keywords:['sungai rasau', 'village'] },
    { title:'Simpang Empat', subtitle:'Simpang Empat, Rambutan/Banyuasin/Palembang', lat:-3.155362, lng:104.828872, keywords:['simpang empat', 'village'] },
    { title:'Sungai Pinang', subtitle:'Sungai Pinang, Rambutan/Banyuasin/Palembang', lat:-3.037647, lng:104.837599, keywords:['sungai pinang', 'village'] },
    { title:'Terusan Jawa', subtitle:'Terusan Jawa, Rambutan/Banyuasin/Palembang', lat:-3.163836, lng:104.852062, keywords:['terusan jawa', 'village'] },
    { title:'Suka Pindah', subtitle:'Suka Pindah, Rambutan/Banyuasin/Palembang', lat:-3.037491, lng:104.969975, keywords:['suka pindah', 'village'] },
    { title:'Mariana', subtitle:'Mariana, Rambutan/Banyuasin/Palembang', lat:-2.978784, lng:104.867482, keywords:['mariana', 'suburb'] },
    { title:'12 Ulu', subtitle:'12 Ulu, Rambutan/Banyuasin/Palembang', lat:-2.98982, lng:104.772032, keywords:['12 ulu', 'village'] },
    { title:'Pemulutan Ilir', subtitle:'Pemulutan Ilir, Rambutan/Banyuasin/Palembang', lat:-3.110524, lng:104.793955, keywords:['pemulutan ilir', 'village'] },
    { title:'Sungai Dua', subtitle:'Sungai Dua, Rambutan/Banyuasin/Palembang', lat:-3.05701, lng:104.863398, keywords:['sungai dua', 'village'] },
    { title:'Tuan Kentang', subtitle:'Tuan Kentang, Rambutan/Banyuasin/Palembang', lat:-3.016722, lng:104.757699, keywords:['tuan kentang', 'village'] },
    { title:'Gelebak Dalam', subtitle:'Gelebak Dalam, Rambutan/Banyuasin/Palembang', lat:-3.085491, lng:104.865292, keywords:['gelebak dalam', 'village'] },
    { title:'Menten', subtitle:'Menten, Rambutan/Banyuasin/Palembang', lat:-3.058739, lng:104.889234, keywords:['menten', 'village'] },
    { title:'2 Ulu', subtitle:'2 Ulu, Rambutan/Banyuasin/Palembang', lat:-3.009516, lng:104.75412, keywords:['2 ulu', 'village'] },
    { title:'Tanah Lembak', subtitle:'Tanah Lembak, Rambutan/Banyuasin/Palembang', lat:-3.13849, lng:104.969933, keywords:['tanah lembak', 'village'] },
    { title:'16 Ulu', subtitle:'16 Ulu, Rambutan/Banyuasin/Palembang', lat:-2.989298, lng:104.792709, keywords:['16 ulu', 'village'] },
    { title:'13 Ulu', subtitle:'13 Ulu, Rambutan/Banyuasin/Palembang', lat:-2.98738, lng:104.7736, keywords:['13 ulu', 'village'] },
    { title:'10 Ulu', subtitle:'10 Ulu, Rambutan/Banyuasin/Palembang', lat:-2.991987, lng:104.766601, keywords:['10 ulu', 'village'] },
    { title:'7 Ulu', subtitle:'7 Ulu, Rambutan/Banyuasin/Palembang', lat:-2.995887, lng:104.762943, keywords:['7 ulu', 'village'] },
    { title:'Tangga Takat', subtitle:'Tangga Takat, Rambutan/Banyuasin/Palembang', lat:-2.987698, lng:104.79, keywords:['tangga takat', 'village'] },
    { title:'8 Ulu', subtitle:'8 Ulu, Rambutan/Banyuasin/Palembang', lat:-3.001512, lng:104.768396, keywords:['8 ulu', 'village'] },
    { title:'Silabranti', subtitle:'Silabranti, Rambutan/Banyuasin/Palembang', lat:-3.001527, lng:104.773758, keywords:['silabranti', 'village'] },
    { title:'Masjid Al-Aqobah 1', subtitle:'Masjid Al-Aqobah 1, Rambutan/Banyuasin/Palembang', lat:-2.974619, lng:104.79975, keywords:['masjid al-aqobah 1', 'place_of_worship'] },
    { title:'Masjid Darusallam', subtitle:'Masjid Darusallam, Rambutan/Banyuasin/Palembang', lat:-3.002425, lng:104.765765, keywords:['masjid darusallam', 'place_of_worship'] },
    { title:'Matahari Department Store', subtitle:'Matahari Department Store, Rambutan/Banyuasin/Palembang', lat:-3.036126, lng:104.791101, keywords:['matahari department store', 'location'] },
    { title:'Mal Opi Mall', subtitle:'Mal Opi Mall, Rambutan/Banyuasin/Palembang', lat:-3.036169, lng:104.791846, keywords:['mal opi mall', 'location'] },
    { title:'bengkel motor sentosa', subtitle:'bengkel motor sentosa, Rambutan/Banyuasin/Palembang', lat:-2.994185, lng:104.804403, keywords:['bengkel motor sentosa', 'location'] },
    { title:'Masjid Al-Muttaqin', subtitle:'Masjid Al-Muttaqin, Rambutan/Banyuasin/Palembang', lat:-2.99275, lng:104.778234, keywords:['masjid al-muttaqin', 'place_of_worship'] },
    { title:'Halte Bungaran', subtitle:'Halte Bungaran, Rambutan/Banyuasin/Palembang', lat:-3.000876, lng:104.766784, keywords:['halte bungaran', 'location'] },
    { title:'Pangkalan Gelebak', subtitle:'Pangkalan Gelebak, Rambutan/Banyuasin/Palembang', lat:-3.065857, lng:104.862471, keywords:['pangkalan gelebak', 'village'] },
    { title:'Masjid Baiturohim Sako', subtitle:'Masjid Baiturohim Sako, Rambutan/Banyuasin/Palembang', lat:-3.074185, lng:104.874854, keywords:['masjid baiturohim sako', 'place_of_worship'] },
    { title:'Masjid Taufiqurrahman', subtitle:'Masjid Taufiqurrahman, Rambutan/Banyuasin/Palembang', lat:-3.057106, lng:104.862873, keywords:['masjid taufiqurrahman', 'place_of_worship'] },
    { title:'Masjid Al-Muttaqin Desa Sungai Dua', subtitle:'Masjid Al-Muttaqin Desa Sungai Dua, Rambutan/Banyuasin/Palembang', lat:-3.05408, lng:104.863154, keywords:['masjid al-muttaqin desa sungai dua', 'place_of_worship'] },
    { title:'Masjid Pembela Agung Alhusna Desa Pangkalan Gelebak', subtitle:'Masjid Pembela Agung Alhusna Desa Pangkalan Gelebak, Rambutan/Banyuasin/Palembang', lat:-3.067101, lng:104.862384, keywords:['masjid pembela agung alhusna desa pangkalan gelebak', 'place_of_worship'] },
    { title:'Masjid Baiturrahman', subtitle:'Masjid Baiturrahman, Rambutan/Banyuasin/Palembang', lat:-3.020043, lng:104.834438, keywords:['masjid baiturrahman', 'place_of_worship'] },
    { title:'Masjid Pajrul Iman', subtitle:'Masjid Pajrul Iman, Rambutan/Banyuasin/Palembang', lat:-3.019454, lng:104.830479, keywords:['masjid pajrul iman', 'place_of_worship'] },
    { title:'Masjid Jami\' Ridwanullah', subtitle:'Masjid Jami\' Ridwanullah, Rambutan/Banyuasin/Palembang', lat:-3.023418, lng:104.832871, keywords:["masjid jami\\' ridwanullah", 'place_of_worship'] },
    { title:'Musholla Al Ikhlas Bukit Hijau 2', subtitle:'Musholla Al Ikhlas Bukit Hijau 2, Rambutan/Banyuasin/Palembang', lat:-3.022323, lng:104.834299, keywords:['musholla al ikhlas bukit hijau 2', 'place_of_worship'] },
    { title:'Masjid Al Ikhlas', subtitle:'Masjid Al Ikhlas, Rambutan/Banyuasin/Palembang', lat:-3.170883, lng:104.877676, keywords:['masjid al ikhlas', 'place_of_worship'] },
    { title:'Rumah Ibadah PLN', subtitle:'Rumah Ibadah PLN, Rambutan/Banyuasin/Palembang', lat:-3.009196, lng:104.790264, keywords:['rumah ibadah pln', 'location'] },
    { title:'Plaza JSC', subtitle:'Plaza JSC, Rambutan/Banyuasin/Palembang', lat:-3.014133, lng:104.792077, keywords:['plaza jsc', 'location'] },
    { title:'Wachout Parfume', subtitle:'Wachout Parfume, Rambutan/Banyuasin/Palembang', lat:-2.984769, lng:104.782051, keywords:['wachout parfume', 'location'] },
    { title:'Indomaret', subtitle:'Indomaret, Rambutan/Banyuasin/Palembang', lat:-3.04939, lng:104.742708, keywords:['indomaret', 'location'] },
    { title:'Musholla Ad-Dzikroyat', subtitle:'Musholla Ad-Dzikroyat, Rambutan/Banyuasin/Palembang', lat:-2.984662, lng:104.781387, keywords:['musholla ad-dzikroyat', 'place_of_worship'] },
    { title:'Toko Manhaluna Parfume', subtitle:'Toko Manhaluna Parfume, Rambutan/Banyuasin/Palembang', lat:-2.987597, lng:104.780771, keywords:['toko manhaluna parfume', 'location'] },
    { title:'Masjid Al Haddad', subtitle:'Masjid Al Haddad, Rambutan/Banyuasin/Palembang', lat:-2.986176, lng:104.775038, keywords:['masjid al haddad', 'place_of_worship'] },
    { title:'Pemulutan', subtitle:'Pemulutan, Rambutan/Banyuasin/Palembang', lat:-3.115914, lng:104.776072, keywords:['pemulutan', 'town'] },
    { title:'Perumahan Pertamina Plaju', subtitle:'Perumahan Pertamina Plaju, Rambutan/Banyuasin/Palembang', lat:-2.997197, lng:104.823936, keywords:['perumahan pertamina plaju', 'suburb'] },
    { title:'Mariana Ilir', subtitle:'Mariana Ilir, Rambutan/Banyuasin/Palembang', lat:-2.973037, lng:104.870636, keywords:['mariana ilir', 'suburb'] },
    { title:'Alfamart', subtitle:'Alfamart, Rambutan/Banyuasin/Palembang', lat:-3.003166, lng:104.771858, keywords:['alfamart', 'location'] },
    { title:'Halte PT. Ali B', subtitle:'Halte PT. Ali B, Rambutan/Banyuasin/Palembang', lat:-3.013102, lng:104.756483, keywords:['halte pt. ali b', 'location'] },
    { title:'Fresh Ulu', subtitle:'Fresh Ulu, Rambutan/Banyuasin/Palembang', lat:-3.002077, lng:104.765901, keywords:['fresh ulu', 'location'] },
    { title:'ivo Hijab Fashion', subtitle:'ivo Hijab Fashion, Rambutan/Banyuasin/Palembang', lat:-3.014233, lng:104.755478, keywords:['ivo hijab fashion', 'location'] },
    { title:'Studio Foto Raja', subtitle:'Studio Foto Raja, Rambutan/Banyuasin/Palembang', lat:-3.004353, lng:104.76365, keywords:['studio foto raja', 'location'] },
    { title:'Masjid Al-Mustanir', subtitle:'Masjid Al-Mustanir, Rambutan/Banyuasin/Palembang', lat:-3.033076, lng:104.791629, keywords:['masjid al-mustanir', 'place_of_worship'] },
    { title:'Citra Lestari Mobilindo', subtitle:'Citra Lestari Mobilindo, Rambutan/Banyuasin/Palembang', lat:-3.037845, lng:104.750222, keywords:['citra lestari mobilindo', 'location'] },
    { title:'Pulau Kemaro', subtitle:'Pulau Kemaro, Rambutan/Banyuasin/Palembang', lat:-2.979823, lng:104.820651, keywords:['pulau kemaro', 'island'] },
    { title:'Pulau Borang', subtitle:'Pulau Borang, Rambutan/Banyuasin/Palembang', lat:-2.913789, lng:104.877538, keywords:['pulau borang', 'island'] },
    { title:'Pulau Banjar', subtitle:'Pulau Banjar, Rambutan/Banyuasin/Palembang', lat:-2.958514, lng:104.872484, keywords:['pulau banjar', 'island'] },
    { title:'Wisma Atlet Jakabaring', subtitle:'Wisma Atlet Jakabaring, Rambutan/Banyuasin/Palembang', lat:-3.017736, lng:104.791454, keywords:['wisma atlet jakabaring', 'residential'] },
    { title:'Perumahan Pertamina Sungai Gerong', subtitle:'Perumahan Pertamina Sungai Gerong, Rambutan/Banyuasin/Palembang', lat:-2.988265, lng:104.853628, keywords:['perumahan pertamina sungai gerong', 'residential'] },
    { title:'Kantor Desa Sungai Dua', subtitle:'Kantor Desa Sungai Dua, Rambutan/Banyuasin/Palembang', lat:-3.05703, lng:104.863436, keywords:['kantor desa sungai dua', 'village'] },
    { title:'Anggrek Residence', subtitle:'Anggrek Residence, Rambutan/Banyuasin/Palembang', lat:-3.043535, lng:104.783538, keywords:['anggrek residence', 'residential'] },
    { title:'Perumahan Alexandria', subtitle:'Perumahan Alexandria, Rambutan/Banyuasin/Palembang', lat:-3.041376, lng:104.789031, keywords:['perumahan alexandria', 'residential'] },
    { title:'Cluster Cendana', subtitle:'Cluster Cendana, Rambutan/Banyuasin/Palembang', lat:-3.041897, lng:104.786286, keywords:['cluster cendana', 'residential'] },
    { title:'Cluster PNS OPI', subtitle:'Cluster PNS OPI, Rambutan/Banyuasin/Palembang', lat:-3.049686, lng:104.787854, keywords:['cluster pns opi', 'residential'] },
    { title:'Topaz 3 Regency', subtitle:'Topaz 3 Regency, Rambutan/Banyuasin/Palembang', lat:-3.046606, lng:104.811267, keywords:['topaz 3 regency', 'residential'] },
    { title:'Palembang', subtitle:'Palembang, Rambutan/Banyuasin/Palembang', lat:-2.985401, lng:104.737174, keywords:['palembang', 'city'] },
    { title:'Toko Nia Sako', subtitle:'Sako, Rambutan, Banyuasin, Sumatera Selatan 30967', lat:-3.073002, lng:104.8733955, keywords:["toko nia", "toko nia sako", "nia sako"] },
    { title:'Desa Sako (Kec. Rambutan)', subtitle:'Sako, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.073002, lng:104.8733955, keywords:["sako", "sako rambutan", "desa sako"] },
    { title:'Dusun I Desa Sako', subtitle:'Desa Sako, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.0715, lng:104.871, keywords:["dusun 1 sako", "dusun i sako"] },
    { title:'Dusun II Desa Sako', subtitle:'Desa Sako, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.0745, lng:104.875, keywords:["dusun 2 sako", "dusun ii sako"] },
    { title:'Dusun III Desa Sako', subtitle:'Desa Sako, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.076, lng:104.878, keywords:["dusun 3 sako", "dusun iii sako"] },
    { title:'Desa Sungai Kedukan', subtitle:'Sungai Kedukan, Rambutan, Banyuasin', lat:-3.055, lng:104.825, keywords:["sungai kedukan", "kedukan rambutan"] },
    { title:'Perumahan OPI Indah (Sungai Kedukan)', subtitle:'Sungai Kedukan, Rambutan, Banyuasin', lat:-3.045, lng:104.81, keywords:["opi indah", "perumahan opi indah"] },
    { title:'Perumahan Jakabaring Permai Rambutan', subtitle:'Sungai Kedukan, Rambutan, Banyuasin', lat:-3.05, lng:104.815, keywords:["jakabaring permai rambutan"] },
    { title:'Desa Gelebak Dalam', subtitle:'Gelebak Dalam, Rambutan, Banyuasin', lat:-3.09, lng:104.89, keywords:["gelebak dalam", "gelebak"] },
    { title:'Desa Menten', subtitle:'Menten, Rambutan, Banyuasin', lat:-3.11, lng:104.91, keywords:["menten", "desa menten"] },
    { title:'Desa Kebun Sahang', subtitle:'Kebun Sahang, Rambutan, Banyuasin', lat:-3.12, lng:104.86, keywords:["kebun sahang"] },
    { title:'Desa Suka Pindah', subtitle:'Suka Pindah, Rambutan, Banyuasin', lat:-3.13, lng:104.84, keywords:["suka pindah"] },
    { title:'Desa Tanjungan', subtitle:'Tanjungan, Rambutan, Banyuasin', lat:-3.14, lng:104.88, keywords:["tanjungan rambutan"] },
    { title:'Desa Tanjung Merbu', subtitle:'Tanjung Merbu, Rambutan, Banyuasin', lat:-3.15, lng:104.9, keywords:["tanjung merbu"] },
    { title:'Desa Sako Suban', subtitle:'Sako Suban, Rambutan, Banyuasin', lat:-3.085, lng:104.88, keywords:["sako suban"] },
    { title:'Desa Tanah Lembak', subtitle:'Tanah Lembak, Rambutan, Banyuasin', lat:-3.1, lng:104.83, keywords:["tanah lembak"] },
    { title:'Desa Parit', subtitle:'Parit, Rambutan, Banyuasin', lat:-3.08, lng:104.84, keywords:["desa parit rambutan"] },
    { title:'Desa Durian Gadis', subtitle:'Durian Gadis, Rambutan, Banyuasin', lat:-3.16, lng:104.85, keywords:["durian gadis"] },
    { title:'Desa Suka Vokasi', subtitle:'Suka Vokasi, Rambutan, Banyuasin', lat:-3.095, lng:104.865, keywords:["suka vokasi"] },
    { title:'Kelurahan Mariana', subtitle:'Mariana, Banyuasin I, Banyuasin', lat:-2.99, lng:104.87, keywords:["mariana", "mariana banyuasin"] },
    { title:'Mariana Ilir', subtitle:'Mariana Ilir, Banyuasin I, Banyuasin', lat:-2.985, lng:104.88, keywords:["mariana ilir"] },
    { title:'Desa Perajin / Prajin', subtitle:'Prajin, Banyuasin I, Banyuasin', lat:-3.01, lng:104.85, keywords:["prajin", "perajin", "desa prajin"] },
    { title:'Komperta Sungai Gerong', subtitle:'Sungai Gerong, Banyuasin I, Banyuasin', lat:-2.975, lng:104.84, keywords:["sungai gerong", "komperta sungai gerong"] },
    { title:'Desa Duren Hijau', subtitle:'Duren Hijau, Banyuasin I, Banyuasin', lat:-2.96, lng:104.89, keywords:["duren hijau"] },
    { title:'Desa Tirto Sari', subtitle:'Tirto Sari, Banyuasin I, Banyuasin', lat:-2.94, lng:104.91, keywords:["tirto sari"] },
    { title:'Desa Cinta Manis', subtitle:'Cinta Manis, Banyuasin I, Banyuasin', lat:-2.92, lng:104.93, keywords:["cinta manis banyuasin"] },
    { title:'Kelurahan Plaju Darat', subtitle:'Plaju Darat, Plaju, Palembang', lat:-3.015, lng:104.815, keywords:["plaju darat"] },
    { title:'Kelurahan Plaju Ulu', subtitle:'Plaju Ulu, Plaju, Palembang', lat:-2.998, lng:104.795, keywords:["plaju ulu"] },
    { title:'Komplek Pertamina Plaju', subtitle:'Plaju, Palembang', lat:-2.995, lng:104.805, keywords:["komplek pertamina plaju", "pertamina plaju"] },
    { title:'Perumahan Taman Sasana Patra', subtitle:'Plaju Darat, Plaju, Palembang', lat:-3.02, lng:104.825, keywords:["sasana patra", "taman sasana patra"] },
    { title:'Kelurahan Talang Bubuk', subtitle:'Talang Bubuk, Plaju, Palembang', lat:-3.005, lng:104.8, keywords:["talang bubuk"] },
    { title:'Kelurahan Talang Jamu', subtitle:'Talang Jamu, Plaju, Palembang', lat:-3.01, lng:104.805, keywords:["talang jamu"] },
    { title:'Kelurahan Bagus Kuning', subtitle:'Bagus Kuning, Plaju, Palembang', lat:-2.99, lng:104.79, keywords:["bagus kuning"] },
    { title:'Kelurahan Tangga Takat', subtitle:'Tangga Takat, Seberang Ulu II, Palembang', lat:-2.992, lng:104.78, keywords:["tangga takat"] },
    { title:'Kelurahan 16 Ulu', subtitle:'16 Ulu, Seberang Ulu II, Palembang', lat:-3.0, lng:104.785, keywords:["16 ulu"] },
    { title:'Komplek Perumahan OPI Regency', subtitle:'15 Ulu, Jakabaring, Palembang', lat:-3.022, lng:104.793, keywords:["opi regency", "komplek opi"] },
    { title:'Danau Buatan OPI Jakabaring', subtitle:'15 Ulu, Jakabaring, Palembang', lat:-3.025, lng:104.79, keywords:["danau opi", "wisata danau opi"] },
    { title:'Kelurahan Kemas Rindo', subtitle:'Kemas Rindo, Kertapati, Palembang', lat:-3.015, lng:104.765, keywords:["kemas rindo"] },
    { title:'Kelurahan Silaberanti', subtitle:'Silaberanti, Jakabaring, Palembang', lat:-2.995, lng:104.775, keywords:["silaberanti"] },
    { title:'Desa Pegayut (Pemulutan)', subtitle:'Pegayut, Pemulutan, Ogan Ilir', lat:-3.065, lng:104.75, keywords:["pegayut", "desa pegayut"] },
    { title:'Desa Ibul Besar I', subtitle:'Ibul Besar I, Pemulutan, Ogan Ilir', lat:-3.045, lng:104.74, keywords:["ibul besar 1", "ibul besar i"] },
    { title:'Desa Ibul Besar II', subtitle:'Ibul Besar II, Pemulutan, Ogan Ilir', lat:-3.05, lng:104.735, keywords:["ibul besar 2", "ibul besar ii"] },
    { title:'Desa Ibul Besar III', subtitle:'Ibul Besar III, Pemulutan, Ogan Ilir', lat:-3.055, lng:104.73, keywords:["ibul besar 3", "ibul besar iii"] },
    { title:'Desa Pelabuhan Dalam', subtitle:'Pelabuhan Dalam, Pemulutan, Ogan Ilir', lat:-3.08, lng:104.73, keywords:["pelabuhan dalam"] },
    { title:'Desa Pemulutan Ilir', subtitle:'Pemulutan Ilir, Pemulutan, Ogan Ilir', lat:-3.1, lng:104.71, keywords:["pemulutan ilir"] },
    { title:'Desa Pemulutan Ulu', subtitle:'Pemulutan Ulu, Pemulutan, Ogan Ilir', lat:-3.12, lng:104.7, keywords:["pemulutan ulu"] },
    { title:'Simpang Pelabuhan Dalam', subtitle:'Pemulutan, Ogan Ilir (Jl. Lintas Palembang-Indralaya)', lat:-3.075, lng:104.735, keywords:["simpang pelabuhan dalam"] },
    { title:'Desa Babatan (Pemulutan)', subtitle:'Babatan, Pemulutan, Ogan Ilir', lat:-3.14, lng:104.72, keywords:["babatan pemulutan"] },
    { title:'Desa Air Kumbang Bakti', subtitle:'Air Kumbang, Banyuasin', lat:-3.1, lng:104.95, keywords:["air kumbang bakti"] },
    { title:'Desa Cinta Manis Baru', subtitle:'Air Kumbang, Banyuasin', lat:-3.08, lng:104.96, keywords:["cinta manis baru"] },
    { title:'Desa Sidomulyo (Air Kumbang)', subtitle:'Air Kumbang, Banyuasin', lat:-3.12, lng:104.97, keywords:["sidomulyo air kumbang"] },
    { title:'Desa Pacing (Air Kumbang)', subtitle:'Air Kumbang, Banyuasin', lat:-3.14, lng:104.94, keywords:["pacing air kumbang"] },
    { title:'Desa Padang Hashim', subtitle:'Air Kumbang, Banyuasin', lat:-3.16, lng:104.93, keywords:["padang hashim"] },
    { title:'Desa Tirta Makmur', subtitle:'Air Kumbang, Banyuasin', lat:-3.18, lng:104.95, keywords:["tirta makmur"] },

    // ══════════════════════════════════════════════════════════
    // KOTA PALEMBANG — MALL & PUSAT PERBELANJAAN
    // ══════════════════════════════════════════════════════════
    { title:'Palembang Indah Mall (PIM)', subtitle:'Jl. Kol. Atmo, 24 Ilir, Bukit Kecil, Palembang', lat:-2.9849, lng:104.7536, keywords:['pim','palembang indah mall','mall pim'] },
    { title:'Palembang Square (PS Mall)', subtitle:'Jl. Angkatan 45, Lorok Pakjo, Ilir Barat I, Palembang', lat:-2.9772, lng:104.7431, keywords:['ps mall','palembang square','ps'] },
    { title:'Palembang Trade Center (PTC Mall)', subtitle:'Jl. R. Sukamto, 8 Ilir, Ilir Timur II, Palembang', lat:-2.9645, lng:104.7645, keywords:['ptc','ptc mall','palembang trade center'] },
    { title:'Icon Mall / Transmart Palembang', subtitle:'Jl. Raden Mattaher, 24 Ilir, Bukit Kecil, Palembang', lat:-2.9732, lng:104.7451, keywords:['transmart','icon mall','icon palembang'] },
    { title:'OPI Mall Jakabaring', subtitle:'Jl. Gubernur H. Bastari, Jakabaring, Palembang', lat:-3.0215, lng:104.7925, keywords:['opi mall','opi jakabaring'] },
    { title:'Internasional Plaza (IP Mall)', subtitle:'Jl. Kapten A. Rivai, Bukit Kecil, Palembang', lat:-2.9803, lng:104.7502, keywords:['ip mall','internasional plaza'] },
    { title:'Lotte Mart Palembang', subtitle:'Jl. Basuki Rahmat, 9 Ilir, Ilir Timur I, Palembang', lat:-2.9700, lng:104.7550, keywords:['lotte','lotte mart'] },

    // PALEMBANG — PERUMAHAN
    { title:'Perumahan Kencana Indah', subtitle:'Jl. Macan Lindungan, Bukit Baru, Ilir Barat I, Palembang', lat:-2.9650, lng:104.7350, keywords:['kencana indah','perumahan kencana'] },
    { title:'Perumahan OPI Jakabaring', subtitle:'Komplek OPI, Jakabaring, Palembang', lat:-3.0210, lng:104.7920, keywords:['perumahan opi','opi regency'] },
    { title:'Perumahan Bukit Sejahtera / Poligon', subtitle:'Jl. Bukit Sejahtera, Gandus, Palembang', lat:-2.9970, lng:104.7210, keywords:['poligon','bukit sejahtera','perumahan poligon'] },
    { title:'Perumahan Puri Demang', subtitle:'Jl. Demang Lebar Daun, Lorok Pakjo, Palembang', lat:-2.9680, lng:104.7390, keywords:['puri demang','demang lebar daun'] },
    { title:'Perumahan Griya Candra', subtitle:'Jl. Srijaya Negara, Alang-Alang Lebar, Palembang', lat:-2.9580, lng:104.7250, keywords:['griya candra','candra indah'] },
    { title:'Perumahan Parameswara', subtitle:'Jl. Parameswara, Bukit Baru, Ilir Barat I, Palembang', lat:-2.9630, lng:104.7330, keywords:['parameswara'] },
    { title:'Perumahan Taman Kenten', subtitle:'Jl. Lettu Kahar Muzakir, Sako, Palembang', lat:-2.9240, lng:104.7780, keywords:['taman kenten','kenten'] },
    { title:'Perumahan Charindoland', subtitle:'Jl. Charindoland, Alang-Alang Lebar, Palembang', lat:-2.9480, lng:104.7160, keywords:['charindo','charindoland'] },
    { title:'Perumahan Sako Kenten', subtitle:'Sako, Palembang', lat:-2.9180, lng:104.7830, keywords:['sako kenten','perumahan sako'] },
    { title:'Perumahan Bukit Berlian', subtitle:'Sematang Borang, Palembang', lat:-2.9380, lng:104.6980, keywords:['bukit berlian'] },
    { title:'Perumahan Talang Kelapa', subtitle:'Alang-Alang Lebar, Palembang', lat:-2.9520, lng:104.7100, keywords:['talang kelapa palembang'] },
    { title:'Perumahan Jakabaring Permai', subtitle:'Seberang Ulu I, Palembang', lat:-3.0050, lng:104.7700, keywords:['jakabaring permai'] },
    { title:'Perumahan Kalidoni Permai', subtitle:'Kalidoni, Palembang', lat:-2.9500, lng:104.7850, keywords:['kalidoni','perumahan kalidoni'] },
    { title:'Perumahan Harapan Jaya', subtitle:'Seberang Ulu II, Palembang', lat:-3.0100, lng:104.7800, keywords:['harapan jaya'] },
    { title:'Perumahan Griya Musi Permai', subtitle:'Sematang Borang, Palembang', lat:-2.9300, lng:104.6900, keywords:['griya musi'] },

    // PALEMBANG — JALAN UTAMA
    { title:'Jl. Jendral Sudirman Palembang', subtitle:'Jl. Jend. Sudirman, Palembang', lat:-2.9750, lng:104.7550, keywords:['sudirman','jalan sudirman','jend sudirman'] },
    { title:'Jl. Demang Lebar Daun', subtitle:'Ilir Barat I, Palembang', lat:-2.9680, lng:104.7380, keywords:['demang','demang lebar daun'] },
    { title:'Jl. R. Sukamto', subtitle:'8 Ilir, Ilir Timur II, Palembang', lat:-2.9640, lng:104.7620, keywords:['sukamto','r sukamto'] },
    { title:'Jl. Angkatan 45', subtitle:'Lorok Pakjo, Ilir Barat I, Palembang', lat:-2.9770, lng:104.7420, keywords:['angkatan 45'] },
    { title:'Jl. Veteran Palembang', subtitle:'9 Ilir, Ilir Timur II, Palembang', lat:-2.9790, lng:104.7620, keywords:['veteran','jalan veteran'] },
    { title:'Jl. Basuki Rahmat', subtitle:'Ilir Timur I, Palembang', lat:-2.9700, lng:104.7530, keywords:['basuki rahmat'] },
    { title:'Jl. Kapten A. Rivai', subtitle:'Bukit Kecil, Palembang', lat:-2.9800, lng:104.7480, keywords:['kapten rivai','a rivai'] },
    { title:'Jl. Srijaya Negara', subtitle:'Bukit Lama, Ilir Barat I, Palembang', lat:-2.9840, lng:104.7320, keywords:['srijaya negara','srijaya'] },
    { title:'Jl. Mayor Salim Batubara', subtitle:'9 Ilir, Ilir Timur I, Palembang', lat:-2.9720, lng:104.7600, keywords:['salim batubara','mayor salim'] },
    { title:'Jl. Kol. H. Burlian', subtitle:'Sukarami, Palembang', lat:-2.9410, lng:104.7050, keywords:['burlian','kol burlian'] },
    { title:'Jl. Tanjung Api-Api', subtitle:'Sukarami, Palembang', lat:-2.9300, lng:104.6800, keywords:['tanjung api api'] },

    // PALEMBANG — LANDMARK & FASILITAS
    { title:'Masjid Agung Sultan Mahmud Badaruddin I', subtitle:'19 Ilir, Bukit Kecil, Palembang', lat:-2.9882, lng:104.7601, keywords:['masjid agung','masjid agung palembang'] },
    { title:'Jembatan Ampera', subtitle:'Sungai Musi, Seberang Ulu I, Palembang', lat:-2.9915, lng:104.7635, keywords:['ampera','jembatan ampera','sungai musi'] },
    { title:'Jakabaring Sport City (JSC)', subtitle:'Jl. Gubernur H. Bastari, Jakabaring, Palembang', lat:-3.0185, lng:104.7890, keywords:['jakabaring','stadion jakabaring','sport city','jsc'] },
    { title:'Bandara Sultan Mahmud Badaruddin II', subtitle:'Talang Betutu, Sukarami, Palembang', lat:-2.8970, lng:104.7010, keywords:['bandara','smb ii','airport palembang','smb2'] },
    { title:'Stasiun KA Kertapati', subtitle:'Kemas Rindo, Kertapati, Palembang', lat:-3.0080, lng:104.7580, keywords:['stasiun','kertapati','stasiun kertapati'] },
    { title:'RSUP Dr. Mohammad Hoesin (RSMH)', subtitle:'Jl. Jend. Sudirman Km.3.5, Kemuning, Palembang', lat:-2.9690, lng:104.7510, keywords:['rsmh','rumah sakit hoesin','rsud palembang'] },
    { title:'RS Charitas Palembang', subtitle:'Jl. Jend. Sudirman, Ilir Timur II, Palembang', lat:-2.9660, lng:104.7530, keywords:['charitas','rs charitas'] },
    { title:'Universitas Sriwijaya Kampus Bukit', subtitle:'Jl. Srijaya Negara, Bukit Lama, Palembang', lat:-2.9840, lng:104.7320, keywords:['unsri','unsri bukit','universitas sriwijaya'] },
    { title:'Universitas Bina Darma (UBD)', subtitle:'Jl. Jend. Ahmad Yani, Palembang', lat:-2.9850, lng:104.7620, keywords:['bina darma','ubd'] },
    { title:'Pasar Kuto Palembang', subtitle:'Jl. Pasar Kuto, 22 Ilir, Bukit Kecil, Palembang', lat:-2.9870, lng:104.7570, keywords:['pasar kuto','kuto'] },
    { title:'Pasar 16 Ilir Palembang', subtitle:'Jl. Jend. Sudirman, 16 Ilir, Palembang', lat:-2.9800, lng:104.7590, keywords:['pasar 16 ilir','16 ilir'] },

    // PALEMBANG — 18 KECAMATAN
    { title:'Kec. Ilir Barat I — Palembang', subtitle:'Ilir Barat I, Palembang', lat:-2.9750, lng:104.7350, keywords:['ilir barat 1','ilir barat i','lorok pakjo','bukit lama'] },
    { title:'Kec. Ilir Barat II — Palembang', subtitle:'Ilir Barat II, Palembang', lat:-2.9880, lng:104.7250, keywords:['ilir barat 2','ilir barat ii','gandus ilbar'] },
    { title:'Kec. Ilir Timur I — Palembang', subtitle:'Ilir Timur I, Palembang', lat:-2.9700, lng:104.7600, keywords:['ilir timur 1','ilir timur i','ilti1'] },
    { title:'Kec. Ilir Timur II — Palembang', subtitle:'Ilir Timur II, Palembang', lat:-2.9650, lng:104.7700, keywords:['ilir timur 2','ilir timur ii','ilti2'] },
    { title:'Kec. Ilir Timur III — Palembang', subtitle:'Ilir Timur III, Palembang', lat:-2.9550, lng:104.7780, keywords:['ilir timur 3','ilir timur iii'] },
    { title:'Kec. Seberang Ulu I — Palembang', subtitle:'Seberang Ulu I, Palembang', lat:-3.0000, lng:104.7650, keywords:['seberang ulu 1','seberang ulu i','su1','kertapati wilayah'] },
    { title:'Kec. Seberang Ulu II — Palembang', subtitle:'Seberang Ulu II, Palembang', lat:-3.0100, lng:104.7850, keywords:['seberang ulu 2','seberang ulu ii','su2','plaju wilayah'] },
    { title:'Kec. Bukit Kecil — Palembang', subtitle:'Bukit Kecil, Palembang', lat:-2.9850, lng:104.7530, keywords:['bukit kecil'] },
    { title:'Kec. Kemuning — Palembang', subtitle:'Kemuning, Palembang', lat:-2.9690, lng:104.7490, keywords:['kemuning'] },
    { title:'Kec. Gandus — Palembang', subtitle:'Gandus, Palembang', lat:-2.9950, lng:104.7200, keywords:['gandus'] },
    { title:'Kec. Kertapati — Palembang', subtitle:'Kertapati, Palembang', lat:-3.0080, lng:104.7600, keywords:['kertapati'] },
    { title:'Kec. Plaju — Palembang', subtitle:'Plaju, Palembang', lat:-2.9980, lng:104.7950, keywords:['plaju','pertamina plaju'] },
    { title:'Kec. Kalidoni — Palembang', subtitle:'Kalidoni, Palembang', lat:-2.9500, lng:104.7850, keywords:['kalidoni'] },
    { title:'Kec. Sako — Palembang', subtitle:'Sako, Palembang', lat:-2.9200, lng:104.7800, keywords:['sako'] },
    { title:'Kec. Sematang Borang — Palembang', subtitle:'Sematang Borang, Palembang', lat:-2.9350, lng:104.6990, keywords:['sematang borang','sematang'] },
    { title:'Kec. Sukarami — Palembang', subtitle:'Sukarami, Palembang', lat:-2.9350, lng:104.7100, keywords:['sukarami'] },
    { title:'Kec. Alang-Alang Lebar — Palembang', subtitle:'Alang-Alang Lebar, Palembang', lat:-2.9500, lng:104.7150, keywords:['alang alang lebar','alang-alang lebar','aal'] },
    { title:'Kec. Jakabaring — Palembang', subtitle:'Jakabaring, Seberang Ulu I, Palembang', lat:-3.0200, lng:104.7900, keywords:['kecamatan jakabaring'] },

    // ══════════════════════════════════════════════════════════
    // KOTA PRABUMULIH — 6 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kota Prabumulih', subtitle:'Prabumulih, Sumatera Selatan', lat:-3.4310, lng:104.2310, keywords:['prabumulih','kota prabumulih'] },
    { title:'Kec. Prabumulih Barat', subtitle:'Prabumulih Barat, Kota Prabumulih', lat:-3.4350, lng:104.2100, keywords:['prabumulih barat'] },
    { title:'Kec. Prabumulih Timur', subtitle:'Prabumulih Timur, Kota Prabumulih', lat:-3.4250, lng:104.2450, keywords:['prabumulih timur'] },
    { title:'Kec. Prabumulih Utara', subtitle:'Prabumulih Utara, Kota Prabumulih', lat:-3.4050, lng:104.2350, keywords:['prabumulih utara'] },
    { title:'Kec. Prabumulih Selatan', subtitle:'Prabumulih Selatan, Kota Prabumulih', lat:-3.4600, lng:104.2250, keywords:['prabumulih selatan'] },
    { title:'Kec. Cambai — Prabumulih', subtitle:'Cambai, Kota Prabumulih', lat:-3.4500, lng:104.2500, keywords:['cambai','prabumulih cambai'] },
    { title:'Kec. Rambang Kapak Tengah — Prabumulih', subtitle:'Rambang Kapak Tengah, Kota Prabumulih', lat:-3.3800, lng:104.2000, keywords:['rambang kapak tengah','rkt prabumulih'] },
    { title:'Pasar Prabumulih', subtitle:'Pusat Kota Prabumulih, Sumatera Selatan', lat:-3.4280, lng:104.2290, keywords:['pasar prabumulih'] },

    // ══════════════════════════════════════════════════════════
    // KOTA LUBUKLINGGAU — 8 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kota Lubuklinggau', subtitle:'Lubuklinggau, Sumatera Selatan', lat:-3.2960, lng:102.8610, keywords:['lubuklinggau','linggau','kota linggau'] },
    { title:'Kec. Lubuklinggau Barat I', subtitle:'Lubuklinggau Barat I, Kota Lubuklinggau', lat:-3.2900, lng:102.8450, keywords:['lubuklinggau barat 1','linggau barat i'] },
    { title:'Kec. Lubuklinggau Barat II', subtitle:'Lubuklinggau Barat II, Kota Lubuklinggau', lat:-3.3050, lng:102.8400, keywords:['lubuklinggau barat 2','linggau barat ii'] },
    { title:'Kec. Lubuklinggau Timur I', subtitle:'Lubuklinggau Timur I, Kota Lubuklinggau', lat:-3.2850, lng:102.8750, keywords:['lubuklinggau timur 1','linggau timur i'] },
    { title:'Kec. Lubuklinggau Timur II', subtitle:'Lubuklinggau Timur II, Kota Lubuklinggau', lat:-3.3000, lng:102.8800, keywords:['lubuklinggau timur 2','linggau timur ii'] },
    { title:'Kec. Lubuklinggau Utara I', subtitle:'Lubuklinggau Utara I, Kota Lubuklinggau', lat:-3.2700, lng:102.8650, keywords:['lubuklinggau utara 1','linggau utara i'] },
    { title:'Kec. Lubuklinggau Utara II', subtitle:'Lubuklinggau Utara II, Kota Lubuklinggau', lat:-3.2550, lng:102.8600, keywords:['lubuklinggau utara 2','linggau utara ii'] },
    { title:'Kec. Lubuklinggau Selatan I', subtitle:'Lubuklinggau Selatan I, Kota Lubuklinggau', lat:-3.3100, lng:102.8600, keywords:['lubuklinggau selatan 1','linggau selatan i'] },
    { title:'Kec. Lubuklinggau Selatan II', subtitle:'Lubuklinggau Selatan II, Kota Lubuklinggau', lat:-3.3250, lng:102.8550, keywords:['lubuklinggau selatan 2','linggau selatan ii'] },
    { title:'Pasar Lubuklinggau', subtitle:'Pusat Kota Lubuklinggau', lat:-3.2950, lng:102.8620, keywords:['pasar linggau','pasar lubuklinggau'] },

    // ══════════════════════════════════════════════════════════
    // KOTA PAGAR ALAM — 5 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kota Pagar Alam', subtitle:'Pagar Alam, Sumatera Selatan', lat:-4.0200, lng:103.2500, keywords:['pagar alam','pagaralam','kota pagaralam'] },
    { title:'Kec. Dempo Utara — Pagar Alam', subtitle:'Dempo Utara, Kota Pagar Alam', lat:-4.0050, lng:103.2600, keywords:['dempo utara','pagar alam utara'] },
    { title:'Kec. Dempo Tengah — Pagar Alam', subtitle:'Dempo Tengah, Kota Pagar Alam', lat:-4.0200, lng:103.2400, keywords:['dempo tengah'] },
    { title:'Kec. Dempo Selatan — Pagar Alam', subtitle:'Dempo Selatan, Kota Pagar Alam', lat:-4.0400, lng:103.2300, keywords:['dempo selatan'] },
    { title:'Kec. Pagar Alam Utara', subtitle:'Pagar Alam Utara, Kota Pagar Alam', lat:-3.9900, lng:103.2500, keywords:['pagar alam utara'] },
    { title:'Kec. Pagar Alam Selatan', subtitle:'Pagar Alam Selatan, Kota Pagar Alam', lat:-4.0250, lng:103.2450, keywords:['pagar alam selatan'] },
    { title:'Gunung Dempo (Wisata Pagar Alam)', subtitle:'Pagar Alam, Sumatera Selatan', lat:-4.0250, lng:103.1600, keywords:['gunung dempo','dempo'] },

    // ══════════════════════════════════════════════════════════
    // KAB. BANYUASIN — 21 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. Banyuasin — Pangkalan Balai', subtitle:'Pangkalan Balai, Banyuasin, Sumatera Selatan', lat:-2.8710, lng:104.3810, keywords:['banyuasin','pangkalan balai','kab banyuasin'] },
    { title:'Kec. Banyuasin I — Mariana', subtitle:'Mariana, Banyuasin I, Banyuasin', lat:-2.7800, lng:104.5500, keywords:['banyuasin 1','banyuasin i','mariana'] },
    { title:'Kec. Banyuasin II — Sungsang', subtitle:'Sungsang, Banyuasin II, Banyuasin', lat:-2.2500, lng:104.7500, keywords:['banyuasin 2','banyuasin ii','sungsang'] },
    { title:'Kec. Banyuasin III — Pangkalan Balai', subtitle:'Pangkalan Balai, Banyuasin III, Banyuasin', lat:-2.8710, lng:104.3810, keywords:['banyuasin 3','banyuasin iii','pangkalan balai'] },
    { title:'Kec. Betung — Banyuasin', subtitle:'Betung, Banyuasin', lat:-3.0000, lng:104.2500, keywords:['betung','betung banyuasin'] },
    { title:'Kec. Muara Padang — Banyuasin', subtitle:'Muara Padang, Banyuasin', lat:-3.2000, lng:104.4000, keywords:['muara padang'] },
    { title:'Kec. Muara Sugihan — Banyuasin', subtitle:'Muara Sugihan, Banyuasin', lat:-2.4500, lng:105.0000, keywords:['muara sugihan'] },
    { title:'Kec. Muara Telang — Banyuasin', subtitle:'Muara Telang, Banyuasin', lat:-2.5000, lng:104.7500, keywords:['muara telang','telang'] },
    { title:'Kec. Pulau Rimau — Banyuasin', subtitle:'Pulau Rimau, Banyuasin', lat:-3.0500, lng:104.0500, keywords:['pulau rimau'] },
    { title:'Kec. Rambutan — Banyuasin', subtitle:'Rambutan, Banyuasin', lat:-3.0700, lng:104.5000, keywords:['rambutan','rambutan banyuasin'] },
    { title:'Desa Sako (Kec. Rambutan)', subtitle:'Sako, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.073002, lng:104.8733955, keywords:['sako','sako rambutan','desa sako'] },
    { title:'Toko Nia Sako', subtitle:'Sako, Rambutan, Banyuasin, Sumatera Selatan 30967', lat:-3.073002, lng:104.8733955, keywords:['toko nia','toko nia sako','nia sako'] },
    { title:'Kec. Rantau Bayur — Banyuasin', subtitle:'Rantau Bayur, Banyuasin', lat:-2.6800, lng:104.0800, keywords:['rantau bayur'] },
    { title:'Kec. Sembawa — Banyuasin', subtitle:'Sembawa, Banyuasin', lat:-2.9700, lng:104.4700, keywords:['sembawa'] },
    { title:'Kec. Suak Tapeh — Banyuasin', subtitle:'Suak Tapeh, Banyuasin', lat:-3.1200, lng:104.1000, keywords:['suak tapeh'] },
    { title:'Kec. Talang Kelapa — Banyuasin', subtitle:'Talang Kelapa, Banyuasin', lat:-2.9100, lng:104.6500, keywords:['talang kelapa banyuasin'] },
    { title:'Kec. Tanjung Lago — Banyuasin', subtitle:'Tanjung Lago, Banyuasin', lat:-2.6200, lng:104.5200, keywords:['tanjung lago'] },
    { title:'Kec. Air Kumbang — Banyuasin', subtitle:'Air Kumbang, Banyuasin', lat:-3.1000, lng:104.7000, keywords:['air kumbang'] },
    { title:'Kec. Air Salek — Banyuasin', subtitle:'Air Salek, Banyuasin', lat:-3.3500, lng:104.7000, keywords:['air salek'] },
    { title:'Kec. Makarti Jaya — Banyuasin', subtitle:'Makarti Jaya, Banyuasin', lat:-2.3500, lng:104.8500, keywords:['makarti jaya'] },
    { title:'Kec. Karang Agung Ilir — Banyuasin', subtitle:'Karang Agung Ilir, Banyuasin', lat:-2.6000, lng:104.3500, keywords:['karang agung ilir'] },
    { title:'Kec. Karang Agung Ulu — Banyuasin', subtitle:'Karang Agung Ulu, Banyuasin', lat:-2.7800, lng:104.1500, keywords:['karang agung ulu'] },
    { title:'Kec. Tungkal Ilir — Banyuasin', subtitle:'Tungkal Ilir, Banyuasin', lat:-3.1500, lng:103.9500, keywords:['tungkal ilir'] },
    { title:'Kec. Tanjung Api-Api — Banyuasin', subtitle:'Tanjung Api-Api, Banyuasin', lat:-2.7000, lng:104.3500, keywords:['tanjung api api banyuasin'] },

    // ══════════════════════════════════════════════════════════
    // KAB. MUSI BANYUASIN (MUBA) — 14 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. Musi Banyuasin — Sekayu', subtitle:'Sekayu, Musi Banyuasin, Sumatera Selatan', lat:-2.8880, lng:103.8410, keywords:['muba','musi banyuasin','sekayu'] },
    { title:'Kec. Sekayu (Ibukota MUBA)', subtitle:'Sekayu, Musi Banyuasin', lat:-2.8880, lng:103.8410, keywords:['kecamatan sekayu','sekayu muba'] },
    { title:'Kec. Babat Supat — Muba', subtitle:'Babat Supat, Musi Banyuasin', lat:-3.0500, lng:103.8000, keywords:['babat supat'] },
    { title:'Kec. Babat Toman — Muba', subtitle:'Babat Toman, Musi Banyuasin', lat:-3.1200, lng:103.9400, keywords:['babat toman'] },
    { title:'Kec. Batanghari Leko — Muba', subtitle:'Batanghari Leko, Musi Banyuasin', lat:-2.7500, lng:103.5000, keywords:['batanghari leko'] },
    { title:'Kec. Bayung Lencir — Muba', subtitle:'Bayung Lencir, Musi Banyuasin', lat:-2.2200, lng:103.9000, keywords:['bayung lencir'] },
    { title:'Kec. Keluang — Muba', subtitle:'Keluang, Musi Banyuasin', lat:-2.9000, lng:103.6500, keywords:['keluang'] },
    { title:'Kec. Lais — Muba', subtitle:'Lais, Musi Banyuasin', lat:-2.7000, lng:104.0000, keywords:['lais muba'] },
    { title:'Kec. Lalan — Muba', subtitle:'Lalan, Musi Banyuasin', lat:-2.3000, lng:103.6000, keywords:['lalan'] },
    { title:'Kec. Lawang Wetan — Muba', subtitle:'Lawang Wetan, Musi Banyuasin', lat:-2.9500, lng:103.8500, keywords:['lawang wetan'] },
    { title:'Kec. Plakat Tinggi — Muba', subtitle:'Plakat Tinggi, Musi Banyuasin', lat:-3.1000, lng:103.9000, keywords:['plakat tinggi'] },
    { title:'Kec. Sanga Desa — Muba', subtitle:'Sanga Desa, Musi Banyuasin', lat:-2.8000, lng:103.7500, keywords:['sanga desa'] },
    { title:'Kec. Sungai Keruh — Muba', subtitle:'Sungai Keruh, Musi Banyuasin', lat:-2.9500, lng:103.7500, keywords:['sungai keruh'] },
    { title:'Kec. Sungai Lilin — Muba', subtitle:'Sungai Lilin, Musi Banyuasin', lat:-2.6500, lng:103.8000, keywords:['sungai lilin'] },
    { title:'Kec. Tungkal Jaya — Muba', subtitle:'Tungkal Jaya, Musi Banyuasin', lat:-2.5000, lng:103.5500, keywords:['tungkal jaya'] },

    // ══════════════════════════════════════════════════════════
    // KAB. MUSI RAWAS (MURA) — 14 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. Musi Rawas — Muara Beliti', subtitle:'Muara Beliti, Musi Rawas, Sumatera Selatan', lat:-3.0140, lng:102.7290, keywords:['musi rawas','muara beliti','mura'] },
    { title:'Kec. Muara Beliti (Ibukota Mura)', subtitle:'Muara Beliti, Musi Rawas', lat:-3.0140, lng:102.7290, keywords:['muara beliti mura'] },
    { title:'Kec. BTS Ulu — Musi Rawas', subtitle:'BTS Ulu, Musi Rawas', lat:-3.0500, lng:102.7800, keywords:['bts ulu','bts ulu mura'] },
    { title:'Kec. Jayaloka — Musi Rawas', subtitle:'Jayaloka, Musi Rawas', lat:-3.1000, lng:102.8500, keywords:['jayaloka'] },
    { title:'Kec. Megang Sakti — Musi Rawas', subtitle:'Megang Sakti, Musi Rawas', lat:-3.2000, lng:102.8800, keywords:['megang sakti'] },
    { title:'Kec. Muara Kelingi — Musi Rawas', subtitle:'Muara Kelingi, Musi Rawas', lat:-3.2600, lng:102.9500, keywords:['muara kelingi'] },
    { title:'Kec. Muara Lakitan — Musi Rawas', subtitle:'Muara Lakitan, Musi Rawas', lat:-3.3500, lng:102.8000, keywords:['muara lakitan'] },
    { title:'Kec. Purwodadi — Musi Rawas', subtitle:'Purwodadi, Musi Rawas', lat:-3.2000, lng:102.9000, keywords:['purwodadi mura'] },
    { title:'Kec. Rawas Ilir — Musi Rawas', subtitle:'Rawas Ilir, Musi Rawas', lat:-3.0500, lng:102.6500, keywords:['rawas ilir'] },
    { title:'Kec. Rawas Ulu — Musi Rawas', subtitle:'Rawas Ulu, Musi Rawas', lat:-3.0000, lng:102.6000, keywords:['rawas ulu'] },
    { title:'Kec. Selangit — Musi Rawas', subtitle:'Selangit, Musi Rawas', lat:-3.0000, lng:102.7500, keywords:['selangit'] },
    { title:'Kec. STL Ulu Terawas — Musi Rawas', subtitle:'STL Ulu Terawas, Musi Rawas', lat:-3.0500, lng:102.7800, keywords:['stl ulu terawas','terawas'] },
    { title:'Kec. Sumber Harta — Musi Rawas', subtitle:'Sumber Harta, Musi Rawas', lat:-3.1500, lng:102.8500, keywords:['sumber harta'] },
    { title:'Kec. Tiang Pumpung Kepungut — Musi Rawas', subtitle:'Tiang Pumpung Kepungut, Musi Rawas', lat:-3.1000, lng:102.7000, keywords:['tiang pumpung kepungut'] },
    { title:'Kec. Tugumulyo — Musi Rawas', subtitle:'Tugumulyo, Musi Rawas', lat:-3.0700, lng:102.7500, keywords:['tugumulyo'] },

    // ══════════════════════════════════════════════════════════
    // KAB. MUSI RAWAS UTARA (MURATARA) — 7 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. Muratara — Rupit', subtitle:'Rupit, Musi Rawas Utara, Sumatera Selatan', lat:-2.6500, lng:102.8000, keywords:['muratara','musi rawas utara','rupit'] },
    { title:'Kec. Rupit (Ibukota Muratara)', subtitle:'Rupit, Musi Rawas Utara', lat:-2.6500, lng:102.8000, keywords:['kecamatan rupit'] },
    { title:'Kec. Karang Jaya — Muratara', subtitle:'Karang Jaya, Musi Rawas Utara', lat:-2.7000, lng:102.7000, keywords:['karang jaya muratara'] },
    { title:'Kec. Nibung — Muratara', subtitle:'Nibung, Musi Rawas Utara', lat:-2.5500, lng:102.8500, keywords:['nibung'] },
    { title:'Kec. Rawas Ulu — Muratara', subtitle:'Rawas Ulu, Musi Rawas Utara', lat:-2.8500, lng:102.5500, keywords:['rawas ulu muratara'] },
    { title:'Kec. Ulu Rawas — Muratara', subtitle:'Ulu Rawas, Musi Rawas Utara', lat:-2.9000, lng:102.5000, keywords:['ulu rawas'] },
    { title:'Kec. Muara Rupit — Muratara', subtitle:'Muara Rupit, Musi Rawas Utara', lat:-2.6800, lng:102.8200, keywords:['muara rupit'] },
    { title:'Kec. Jangkat Timur — Muratara', subtitle:'Jangkat Timur, Musi Rawas Utara', lat:-2.6000, lng:102.7500, keywords:['jangkat timur'] },

    // ══════════════════════════════════════════════════════════
    // KAB. LAHAT — 24 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. Lahat — Kota Lahat', subtitle:'Lahat, Sumatera Selatan', lat:-3.7950, lng:103.5410, keywords:['lahat','kota lahat','kab lahat'] },
    { title:'Kec. Lahat (Ibukota)', subtitle:'Lahat, Lahat', lat:-3.7950, lng:103.5410, keywords:['kecamatan lahat'] },
    { title:'Kec. Gumay Ulu — Lahat', subtitle:'Gumay Ulu, Lahat', lat:-4.0000, lng:103.3500, keywords:['gumay ulu'] },
    { title:'Kec. Gumay Talang — Lahat', subtitle:'Gumay Talang, Lahat', lat:-3.9000, lng:103.4500, keywords:['gumay talang'] },
    { title:'Kec. Jarai — Lahat', subtitle:'Jarai, Lahat', lat:-4.0500, lng:103.5000, keywords:['jarai'] },
    { title:'Kec. Kikim Barat — Lahat', subtitle:'Kikim Barat, Lahat', lat:-3.8500, lng:103.3500, keywords:['kikim barat'] },
    { title:'Kec. Kikim Selatan — Lahat', subtitle:'Kikim Selatan, Lahat', lat:-3.9000, lng:103.4000, keywords:['kikim selatan'] },
    { title:'Kec. Kikim Tengah — Lahat', subtitle:'Kikim Tengah, Lahat', lat:-3.8700, lng:103.3800, keywords:['kikim tengah'] },
    { title:'Kec. Kikim Timur — Lahat', subtitle:'Kikim Timur, Lahat', lat:-3.8500, lng:103.4300, keywords:['kikim timur'] },
    { title:'Kec. Kota Agung — Lahat', subtitle:'Kota Agung, Lahat', lat:-3.7500, lng:103.6000, keywords:['kota agung lahat'] },
    { title:'Kec. Merapi Barat — Lahat', subtitle:'Merapi Barat, Lahat', lat:-3.7000, lng:103.6000, keywords:['merapi barat'] },
    { title:'Kec. Merapi Selatan — Lahat', subtitle:'Merapi Selatan, Lahat', lat:-3.7200, lng:103.5700, keywords:['merapi selatan'] },
    { title:'Kec. Merapi Timur — Lahat', subtitle:'Merapi Timur, Lahat', lat:-3.6800, lng:103.6500, keywords:['merapi timur'] },
    { title:'Kec. Muara Payang — Lahat', subtitle:'Muara Payang, Lahat', lat:-3.9500, lng:103.5500, keywords:['muara payang'] },
    { title:'Kec. Mulak Sebingkai — Lahat', subtitle:'Mulak Sebingkai, Lahat', lat:-3.8500, lng:103.6000, keywords:['mulak sebingkai'] },
    { title:'Kec. Mulak Ulu — Lahat', subtitle:'Mulak Ulu, Lahat', lat:-3.9000, lng:103.5500, keywords:['mulak ulu'] },
    { title:'Kec. Pajar Bulan — Lahat', subtitle:'Pajar Bulan, Lahat', lat:-4.1500, lng:103.4500, keywords:['pajar bulan'] },
    { title:'Kec. Pagar Gunung — Lahat', subtitle:'Pagar Gunung, Lahat', lat:-4.0000, lng:103.4000, keywords:['pagar gunung'] },
    { title:'Kec. Pseksu — Lahat', subtitle:'Pseksu, Lahat', lat:-3.8000, lng:103.4500, keywords:['pseksu'] },
    { title:'Kec. Pulau Pinang — Lahat', subtitle:'Pulau Pinang, Lahat', lat:-3.7200, lng:103.6500, keywords:['pulau pinang lahat'] },
    { title:'Kec. Sukamerindu — Lahat', subtitle:'Sukamerindu, Lahat', lat:-3.8500, lng:103.5500, keywords:['sukamerindu'] },
    { title:'Kec. Tanjung Sakti Pumu — Lahat', subtitle:'Tanjung Sakti Pumu, Lahat', lat:-4.1500, lng:103.4500, keywords:['tanjung sakti pumu'] },
    { title:'Kec. Tanjung Sakti Pumi — Lahat', subtitle:'Tanjung Sakti Pumi, Lahat', lat:-4.1200, lng:103.4800, keywords:['tanjung sakti pumi'] },
    { title:'Kec. Tanjung Tebat — Lahat', subtitle:'Tanjung Tebat, Lahat', lat:-3.8700, lng:103.5200, keywords:['tanjung tebat'] },

    // ══════════════════════════════════════════════════════════
    // KAB. MUARA ENIM — 22 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. Muara Enim — Kota Muara Enim', subtitle:'Muara Enim, Sumatera Selatan', lat:-3.6550, lng:103.7780, keywords:['muara enim','kab muara enim'] },
    { title:'Kec. Muara Enim (Ibukota)', subtitle:'Muara Enim, Muara Enim', lat:-3.6550, lng:103.7780, keywords:['kecamatan muara enim'] },
    { title:'Kec. Benakat — Muara Enim', subtitle:'Benakat, Muara Enim', lat:-3.5000, lng:103.7000, keywords:['benakat'] },
    { title:'Kec. Gelumbang — Muara Enim', subtitle:'Gelumbang, Muara Enim', lat:-3.4000, lng:103.9000, keywords:['gelumbang'] },
    { title:'Kec. Gunung Megang — Muara Enim', subtitle:'Gunung Megang, Muara Enim', lat:-3.6000, lng:103.6500, keywords:['gunung megang'] },
    { title:'Kec. Kelekar — Muara Enim', subtitle:'Kelekar, Muara Enim', lat:-3.3500, lng:103.9500, keywords:['kelekar'] },
    { title:'Kec. Lawang Kidul — Muara Enim', subtitle:'Lawang Kidul (Tanjung Enim), Muara Enim', lat:-3.7500, lng:103.8700, keywords:['lawang kidul','tanjung enim','ptba'] },
    { title:'Kec. Lembak — Muara Enim', subtitle:'Lembak, Muara Enim', lat:-3.5000, lng:103.8500, keywords:['lembak'] },
    { title:'Kec. Lubai — Muara Enim', subtitle:'Lubai, Muara Enim', lat:-3.8000, lng:103.7000, keywords:['lubai'] },
    { title:'Kec. Lubai Ulu — Muara Enim', subtitle:'Lubai Ulu, Muara Enim', lat:-3.8500, lng:103.7000, keywords:['lubai ulu'] },
    { title:'Kec. Muara Belida — Muara Enim', subtitle:'Muara Belida, Muara Enim', lat:-3.2500, lng:104.0000, keywords:['muara belida'] },
    { title:'Kec. Penukal — Muara Enim', subtitle:'Penukal, Muara Enim', lat:-3.1500, lng:103.9000, keywords:['penukal muara enim'] },
    { title:'Kec. Penukal Utara — Muara Enim', subtitle:'Penukal Utara, Muara Enim', lat:-3.1000, lng:103.8500, keywords:['penukal utara'] },
    { title:'Kec. Rambang — Muara Enim', subtitle:'Rambang, Muara Enim', lat:-3.4000, lng:103.8000, keywords:['rambang muara enim'] },
    { title:'Kec. Rambang Dangku — Muara Enim', subtitle:'Rambang Dangku, Muara Enim', lat:-3.5000, lng:103.8000, keywords:['rambang dangku'] },
    { title:'Kec. Semende Darat Laut — Muara Enim', subtitle:'Semende Darat Laut, Muara Enim', lat:-4.1500, lng:103.6000, keywords:['semende darat laut','semende'] },
    { title:'Kec. Semende Darat Tengah — Muara Enim', subtitle:'Semende Darat Tengah, Muara Enim', lat:-4.1000, lng:103.5500, keywords:['semende darat tengah'] },
    { title:'Kec. Semende Darat Ulu — Muara Enim', subtitle:'Semende Darat Ulu, Muara Enim', lat:-4.0500, lng:103.5000, keywords:['semende darat ulu'] },
    { title:'Kec. Sungai Rotan — Muara Enim', subtitle:'Sungai Rotan, Muara Enim', lat:-3.2500, lng:103.9500, keywords:['sungai rotan'] },
    { title:'Kec. Tanjung Agung — Muara Enim', subtitle:'Tanjung Agung, Muara Enim', lat:-3.8500, lng:103.8000, keywords:['tanjung agung muara enim'] },
    { title:'Kec. Ujan Mas — Muara Enim', subtitle:'Ujan Mas, Muara Enim', lat:-3.7500, lng:103.7500, keywords:['ujan mas'] },
    { title:'Tanjung Enim (Bukit Asam)', subtitle:'Tanjung Enim, Lawang Kidul, Muara Enim', lat:-3.7500, lng:103.8700, keywords:['tanjung enim','bukit asam','ptba'] },

    // ══════════════════════════════════════════════════════════
    // KAB. EMPAT LAWANG — 10 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. Empat Lawang — Tebing Tinggi', subtitle:'Tebing Tinggi, Empat Lawang, Sumatera Selatan', lat:-3.9700, lng:103.1300, keywords:['empat lawang','tebing tinggi empat lawang'] },
    { title:'Kec. Tebing Tinggi (Ibukota Empat Lawang)', subtitle:'Tebing Tinggi, Empat Lawang', lat:-3.9700, lng:103.1300, keywords:['tebing tinggi','kecamatan tebing tinggi'] },
    { title:'Kec. Lintang Kanan — Empat Lawang', subtitle:'Lintang Kanan, Empat Lawang', lat:-4.0000, lng:103.0500, keywords:['lintang kanan'] },
    { title:'Kec. Muara Pinang — Empat Lawang', subtitle:'Muara Pinang, Empat Lawang', lat:-3.9000, lng:103.2000, keywords:['muara pinang'] },
    { title:'Kec. Ngusang — Empat Lawang', subtitle:'Ngusang, Empat Lawang', lat:-3.8700, lng:103.1000, keywords:['ngusang'] },
    { title:'Kec. Pasemah Air Keruh — Empat Lawang', subtitle:'Pasemah Air Keruh, Empat Lawang', lat:-3.9500, lng:103.2000, keywords:['pasemah air keruh'] },
    { title:'Kec. Pendopo — Empat Lawang', subtitle:'Pendopo, Empat Lawang', lat:-3.9200, lng:103.2700, keywords:['pendopo'] },
    { title:'Kec. Pendopo Barat — Empat Lawang', subtitle:'Pendopo Barat, Empat Lawang', lat:-3.9500, lng:103.2300, keywords:['pendopo barat'] },
    { title:'Kec. Sikap Dalam — Empat Lawang', subtitle:'Sikap Dalam, Empat Lawang', lat:-3.8500, lng:103.1500, keywords:['sikap dalam'] },
    { title:'Kec. Saling — Empat Lawang', subtitle:'Saling, Empat Lawang', lat:-3.9000, lng:103.1500, keywords:['saling'] },
    { title:'Kec. Ulu Musi — Empat Lawang', subtitle:'Ulu Musi, Empat Lawang', lat:-3.8000, lng:103.0000, keywords:['ulu musi'] },

    // ══════════════════════════════════════════════════════════
    // KAB. PENUKAL ABAB LEMATANG ILIR (PALI) — 5 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. PALI — Talang Ubi', subtitle:'Talang Ubi, Penukal Abab Lematang Ilir, Sumatera Selatan', lat:-3.2000, lng:103.9500, keywords:['pali','talang ubi','penukal abab lematang ilir'] },
    { title:'Kec. Talang Ubi (Ibukota PALI)', subtitle:'Talang Ubi, PALI', lat:-3.2000, lng:103.9500, keywords:['kecamatan talang ubi'] },
    { title:'Kec. Abab — PALI', subtitle:'Abab, PALI', lat:-3.1000, lng:103.8500, keywords:['abab','abab pali'] },
    { title:'Kec. Penukal — PALI', subtitle:'Penukal, PALI', lat:-3.1500, lng:103.9000, keywords:['penukal pali'] },
    { title:'Kec. Penukal Utara — PALI', subtitle:'Penukal Utara, PALI', lat:-3.1000, lng:103.8500, keywords:['penukal utara pali'] },
    { title:'Kec. Tanah Abang — PALI', subtitle:'Tanah Abang, PALI', lat:-3.2500, lng:104.0000, keywords:['tanah abang pali'] },

    // ══════════════════════════════════════════════════════════
    // KAB. OGAN ILIR (OI) — 16 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. Ogan Ilir — Indralaya', subtitle:'Indralaya, Ogan Ilir, Sumatera Selatan', lat:-3.2210, lng:104.6510, keywords:['ogan ilir','indralaya','inderalaya','kab oi'] },
    { title:'Kec. Indralaya (Ibukota OI)', subtitle:'Indralaya, Ogan Ilir', lat:-3.2210, lng:104.6510, keywords:['kecamatan indralaya'] },
    { title:'Kec. Indralaya Utara — Ogan Ilir', subtitle:'Indralaya Utara, Ogan Ilir', lat:-3.1800, lng:104.6500, keywords:['indralaya utara'] },
    { title:'Kec. Indralaya Selatan — Ogan Ilir', subtitle:'Indralaya Selatan, Ogan Ilir', lat:-3.2700, lng:104.6500, keywords:['indralaya selatan'] },
    { title:'Kec. Kandis — Ogan Ilir', subtitle:'Kandis, Ogan Ilir', lat:-3.4500, lng:104.5500, keywords:['kandis ogan ilir'] },
    { title:'Kec. Lubuk Keliat — Ogan Ilir', subtitle:'Lubuk Keliat, Ogan Ilir', lat:-3.5500, lng:104.4500, keywords:['lubuk keliat'] },
    { title:'Kec. Muara Kuang — Ogan Ilir', subtitle:'Muara Kuang, Ogan Ilir', lat:-3.4200, lng:104.4800, keywords:['muara kuang'] },
    { title:'Kec. Payaraman — Ogan Ilir', subtitle:'Payaraman, Ogan Ilir', lat:-3.3500, lng:104.5000, keywords:['payaraman'] },
    { title:'Kec. Pemulutan — Ogan Ilir', subtitle:'Pemulutan, Ogan Ilir', lat:-3.2800, lng:104.6800, keywords:['pemulutan'] },
    { title:'Kec. Pemulutan Barat — Ogan Ilir', subtitle:'Pemulutan Barat, Ogan Ilir', lat:-3.3000, lng:104.6200, keywords:['pemulutan barat'] },
    { title:'Kec. Pemulutan Selatan — Ogan Ilir', subtitle:'Pemulutan Selatan, Ogan Ilir', lat:-3.3500, lng:104.6500, keywords:['pemulutan selatan'] },
    { title:'Kec. Rantau Alai — Ogan Ilir', subtitle:'Rantau Alai, Ogan Ilir', lat:-3.5000, lng:104.5000, keywords:['rantau alai'] },
    { title:'Kec. Rantau Panjang — Ogan Ilir', subtitle:'Rantau Panjang, Ogan Ilir', lat:-3.4800, lng:104.4500, keywords:['rantau panjang oi'] },
    { title:'Kec. Rambang Kuang — Ogan Ilir', subtitle:'Rambang Kuang, Ogan Ilir', lat:-3.2500, lng:104.5000, keywords:['rambang kuang'] },
    { title:'Kec. Sungai Pinang — Ogan Ilir', subtitle:'Sungai Pinang, Ogan Ilir', lat:-3.4300, lng:104.5500, keywords:['sungai pinang oi'] },
    { title:'Kec. Tanjung Batu — Ogan Ilir', subtitle:'Tanjung Batu, Ogan Ilir', lat:-3.3900, lng:104.3800, keywords:['tanjung batu oi'] },
    { title:'Kec. Tanjung Raja — Ogan Ilir', subtitle:'Tanjung Raja, Ogan Ilir', lat:-3.3500, lng:104.5500, keywords:['tanjung raja oi'] },
    { title:'Universitas Sriwijaya Kampus Indralaya', subtitle:'Jl. Palembang-Prabumulih, Indralaya, Ogan Ilir', lat:-3.2210, lng:104.6510, keywords:['unsri indralaya','unsri ogi'] },

    // ══════════════════════════════════════════════════════════
    // KAB. OGAN KOMERING ILIR (OKI) — 18 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. OKI — Kayuagung', subtitle:'Kayuagung, Ogan Komering Ilir, Sumatera Selatan', lat:-3.3920, lng:104.9880, keywords:['oki','kayuagung','ogan komering ilir'] },
    { title:'Kec. Kayuagung (Ibukota OKI)', subtitle:'Kayuagung, OKI', lat:-3.3920, lng:104.9880, keywords:['kecamatan kayuagung'] },
    { title:'Kec. Air Sugihan — OKI', subtitle:'Air Sugihan, OKI', lat:-2.9500, lng:105.5000, keywords:['air sugihan'] },
    { title:'Kec. Cengal — OKI', subtitle:'Cengal, OKI', lat:-3.6500, lng:105.3500, keywords:['cengal'] },
    { title:'Kec. Jejawi — OKI', subtitle:'Jejawi, OKI', lat:-3.5000, lng:105.0000, keywords:['jejawi'] },
    { title:'Kec. Lempuing — OKI', subtitle:'Lempuing, OKI', lat:-3.8000, lng:105.2000, keywords:['lempuing'] },
    { title:'Kec. Lempuing Jaya — OKI', subtitle:'Lempuing Jaya, OKI', lat:-3.8200, lng:105.2500, keywords:['lempuing jaya'] },
    { title:'Kec. Mesuji — OKI', subtitle:'Mesuji, OKI', lat:-3.6000, lng:105.0500, keywords:['mesuji'] },
    { title:'Kec. Mesuji Makmur — OKI', subtitle:'Mesuji Makmur, OKI', lat:-3.6200, lng:105.1000, keywords:['mesuji makmur'] },
    { title:'Kec. Mesuji Raya — OKI', subtitle:'Mesuji Raya, OKI', lat:-3.6500, lng:105.0800, keywords:['mesuji raya'] },
    { title:'Kec. Pampangan — OKI', subtitle:'Pampangan, OKI', lat:-3.4500, lng:105.1000, keywords:['pampangan'] },
    { title:'Kec. Pangkalan Lampam — OKI', subtitle:'Pangkalan Lampam, OKI', lat:-3.5500, lng:105.2000, keywords:['pangkalan lampam'] },
    { title:'Kec. Pedamaran — OKI', subtitle:'Pedamaran, OKI', lat:-3.4000, lng:105.1500, keywords:['pedamaran'] },
    { title:'Kec. Pedamaran Timur — OKI', subtitle:'Pedamaran Timur, OKI', lat:-3.4500, lng:105.2000, keywords:['pedamaran timur'] },
    { title:'Kec. Sirah Pulau Padang — OKI', subtitle:'Sirah Pulau Padang, OKI', lat:-3.3000, lng:105.0500, keywords:['sirah pulau padang','sp padang'] },
    { title:'Kec. Sungai Menang — OKI', subtitle:'Sungai Menang, OKI', lat:-4.0000, lng:105.4000, keywords:['sungai menang'] },
    { title:'Kec. Tanjung Lubuk — OKI', subtitle:'Tanjung Lubuk, OKI', lat:-3.5500, lng:105.0500, keywords:['tanjung lubuk oki'] },
    { title:'Kec. Tulung Selapan — OKI', subtitle:'Tulung Selapan, OKI', lat:-3.2000, lng:105.3000, keywords:['tulung selapan'] },

    // ══════════════════════════════════════════════════════════
    // KAB. OGAN KOMERING ULU (OKU) — 13 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. OKU — Baturaja', subtitle:'Baturaja, Ogan Komering Ulu, Sumatera Selatan', lat:-4.1280, lng:104.1670, keywords:['oku','baturaja','ogan komering ulu'] },
    { title:'Kec. Baturaja Barat', subtitle:'Baturaja Barat, OKU', lat:-4.1200, lng:104.1500, keywords:['baturaja barat'] },
    { title:'Kec. Baturaja Timur', subtitle:'Baturaja Timur, OKU', lat:-4.1300, lng:104.1800, keywords:['baturaja timur'] },
    { title:'Kec. Kedaton Peninjauan Raya — OKU', subtitle:'Kedaton Peninjauan Raya, OKU', lat:-4.1000, lng:104.2300, keywords:['kedaton peninjauan raya','kpr'] },
    { title:'Kec. Lengkiti — OKU', subtitle:'Lengkiti, OKU', lat:-4.2000, lng:104.0500, keywords:['lengkiti'] },
    { title:'Kec. Lubuk Batang — OKU', subtitle:'Lubuk Batang, OKU', lat:-4.2000, lng:104.1000, keywords:['lubuk batang'] },
    { title:'Kec. Lubuk Raja — OKU', subtitle:'Lubuk Raja, OKU', lat:-4.0500, lng:104.2000, keywords:['lubuk raja'] },
    { title:'Kec. Muara Jaya — OKU', subtitle:'Muara Jaya, OKU', lat:-4.3000, lng:104.0500, keywords:['muara jaya oku'] },
    { title:'Kec. Pengandonan — OKU', subtitle:'Pengandonan, OKU', lat:-4.3500, lng:104.0500, keywords:['pengandonan'] },
    { title:'Kec. Peninjauan — OKU', subtitle:'Peninjauan, OKU', lat:-4.1000, lng:104.2500, keywords:['peninjauan'] },
    { title:'Kec. Semidang Aji — OKU', subtitle:'Semidang Aji, OKU', lat:-4.0500, lng:104.2000, keywords:['semidang aji'] },
    { title:'Kec. Sinar Peninjauan — OKU', subtitle:'Sinar Peninjauan, OKU', lat:-4.0800, lng:104.2700, keywords:['sinar peninjauan'] },
    { title:'Kec. Sosoh Buay Rayap — OKU', subtitle:'Sosoh Buay Rayap, OKU', lat:-4.1500, lng:104.0000, keywords:['sosoh buay rayap'] },
    { title:'Kec. Ulu Ogan — OKU', subtitle:'Ulu Ogan, OKU', lat:-4.2500, lng:104.0000, keywords:['ulu ogan'] },

    // ══════════════════════════════════════════════════════════
    // KAB. OKU TIMUR — 20 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. OKU Timur — Martapura', subtitle:'Martapura, OKU Timur, Sumatera Selatan', lat:-4.3510, lng:104.3610, keywords:['oku timur','martapura','oku timur martapura'] },
    { title:'Kec. Martapura (Ibukota OKU Timur)', subtitle:'Martapura, OKU Timur', lat:-4.3510, lng:104.3610, keywords:['kecamatan martapura'] },
    { title:'Kec. Belitang — OKU Timur', subtitle:'Belitang, OKU Timur', lat:-4.0800, lng:104.6000, keywords:['belitang'] },
    { title:'Kec. Belitang I — OKU Timur', subtitle:'Belitang I, OKU Timur', lat:-4.0900, lng:104.6100, keywords:['belitang 1','belitang i'] },
    { title:'Kec. Belitang II — OKU Timur', subtitle:'Belitang II, OKU Timur', lat:-4.1000, lng:104.6200, keywords:['belitang 2','belitang ii'] },
    { title:'Kec. Belitang III — OKU Timur', subtitle:'Belitang III, OKU Timur', lat:-4.1100, lng:104.6400, keywords:['belitang 3','belitang iii'] },
    { title:'Kec. Belitang Jaya — OKU Timur', subtitle:'Belitang Jaya, OKU Timur', lat:-4.0700, lng:104.5900, keywords:['belitang jaya'] },
    { title:'Kec. Belitang Madang Raya — OKU Timur', subtitle:'Belitang Madang Raya, OKU Timur', lat:-4.0600, lng:104.5800, keywords:['belitang madang raya'] },
    { title:'Kec. Belitang Mulya — OKU Timur', subtitle:'Belitang Mulya, OKU Timur', lat:-4.1200, lng:104.6500, keywords:['belitang mulya'] },
    { title:'Kec. BP Peliung — OKU Timur', subtitle:'BP Peliung, OKU Timur', lat:-4.2000, lng:104.4500, keywords:['bp peliung','peliung'] },
    { title:'Kec. Buay Madang — OKU Timur', subtitle:'Buay Madang, OKU Timur', lat:-4.2500, lng:104.3500, keywords:['buay madang'] },
    { title:'Kec. Buay Madang Timur — OKU Timur', subtitle:'Buay Madang Timur, OKU Timur', lat:-4.2600, lng:104.3800, keywords:['buay madang timur'] },
    { title:'Kec. Buay Pemuka Bangsa Raja — OKU Timur', subtitle:'Buay Pemuka Bangsa Raja, OKU Timur', lat:-4.3000, lng:104.3000, keywords:['buay pemuka bangsa raja'] },
    { title:'Kec. Buay Pemuka Peliung — OKU Timur', subtitle:'Buay Pemuka Peliung, OKU Timur', lat:-4.2800, lng:104.2800, keywords:['buay pemuka peliung'] },
    { title:'Kec. Bunga Mayang — OKU Timur', subtitle:'Bunga Mayang, OKU Timur', lat:-4.3500, lng:104.3200, keywords:['bunga mayang'] },
    { title:'Kec. Cempaka — OKU Timur', subtitle:'Cempaka, OKU Timur', lat:-4.3200, lng:104.4000, keywords:['cempaka oku timur'] },
    { title:'Kec. Jayapura — OKU Timur', subtitle:'Jayapura, OKU Timur', lat:-4.3800, lng:104.3000, keywords:['jayapura oku timur'] },
    { title:'Kec. Madang Suku I — OKU Timur', subtitle:'Madang Suku I, OKU Timur', lat:-4.2200, lng:104.4200, keywords:['madang suku 1','madang suku i'] },
    { title:'Kec. Madang Suku II — OKU Timur', subtitle:'Madang Suku II, OKU Timur', lat:-4.2300, lng:104.4300, keywords:['madang suku 2','madang suku ii'] },
    { title:'Kec. Madang Suku III — OKU Timur', subtitle:'Madang Suku III, OKU Timur', lat:-4.2400, lng:104.4400, keywords:['madang suku 3','madang suku iii'] },
    { title:'Kec. Semendawai Barat — OKU Timur', subtitle:'Semendawai Barat, OKU Timur', lat:-4.2000, lng:104.5000, keywords:['semendawai barat'] },

    // ══════════════════════════════════════════════════════════
    // KAB. OKU SELATAN — 19 KECAMATAN
    // ══════════════════════════════════════════════════════════
    { title:'Kab. OKU Selatan — Muara Dua', subtitle:'Muara Dua, OKU Selatan, Sumatera Selatan', lat:-4.5200, lng:104.0500, keywords:['oku selatan','muara dua','muara dua oku selatan'] },
    { title:'Kec. Muara Dua (Ibukota OKU Selatan)', subtitle:'Muara Dua, OKU Selatan', lat:-4.5200, lng:104.0500, keywords:['kecamatan muara dua'] },
    { title:'Kec. Banding Agung — OKU Selatan', subtitle:'Banding Agung (Danau Ranau), OKU Selatan', lat:-4.8500, lng:103.9000, keywords:['banding agung','danau ranau'] },
    { title:'Kec. Buay Runjung — OKU Selatan', subtitle:'Buay Runjung, OKU Selatan', lat:-4.6000, lng:104.1000, keywords:['buay runjung'] },
    { title:'Kec. Buay Sandang Aji — OKU Selatan', subtitle:'Buay Sandang Aji, OKU Selatan', lat:-4.6500, lng:104.0500, keywords:['buay sandang aji'] },
    { title:'Kec. Buana Pemaca — OKU Selatan', subtitle:'Buana Pemaca, OKU Selatan', lat:-4.7500, lng:103.9500, keywords:['buana pemaca'] },
    { title:'Kec. Kisam Ilir — OKU Selatan', subtitle:'Kisam Ilir, OKU Selatan', lat:-4.6000, lng:104.0000, keywords:['kisam ilir'] },
    { title:'Kec. Kisam Tinggi — OKU Selatan', subtitle:'Kisam Tinggi, OKU Selatan', lat:-4.6500, lng:103.9500, keywords:['kisam tinggi'] },
    { title:'Kec. Mekakau Ilir — OKU Selatan', subtitle:'Mekakau Ilir, OKU Selatan', lat:-4.6000, lng:104.1000, keywords:['mekakau ilir'] },
    { title:'Kec. Muara Dua Kisam — OKU Selatan', subtitle:'Muara Dua Kisam, OKU Selatan', lat:-4.5400, lng:103.9800, keywords:['muara dua kisam'] },
    { title:'Kec. Pulau Beringin — OKU Selatan', subtitle:'Pulau Beringin, OKU Selatan', lat:-4.5000, lng:104.1000, keywords:['pulau beringin'] },
    { title:'Kec. Runjung Agung — OKU Selatan', subtitle:'Runjung Agung, OKU Selatan', lat:-4.6500, lng:104.1500, keywords:['runjung agung'] },
    { title:'Kec. Simpang — OKU Selatan', subtitle:'Simpang, OKU Selatan', lat:-4.4500, lng:104.0500, keywords:['simpang oku selatan'] },
    { title:'Kec. Sindang Danau — OKU Selatan', subtitle:'Sindang Danau, OKU Selatan', lat:-4.7500, lng:103.9000, keywords:['sindang danau'] },
    { title:'Kec. Sungai Are — OKU Selatan', subtitle:'Sungai Are, OKU Selatan', lat:-4.7000, lng:104.0000, keywords:['sungai are'] },
    { title:'Kec. Sungai Nasal — OKU Selatan', subtitle:'Sungai Nasal, OKU Selatan', lat:-4.7500, lng:104.0500, keywords:['sungai nasal'] },
    { title:'Kec. Tiga Dihaji — OKU Selatan', subtitle:'Tiga Dihaji, OKU Selatan', lat:-4.4000, lng:104.0500, keywords:['tiga dihaji'] },
    { title:'Kec. Ulu Danau — OKU Selatan', subtitle:'Ulu Danau, OKU Selatan', lat:-4.8200, lng:103.8800, keywords:['ulu danau'] },
    { title:'Kec. Warkuk Ranau Selatan — OKU Selatan', subtitle:'Warkuk Ranau Selatan, OKU Selatan', lat:-4.8000, lng:103.8500, keywords:['warkuk ranau selatan'] },
    { title:'Danau Ranau', subtitle:'Banding Agung, OKU Selatan & Lampung Barat', lat:-4.8600, lng:103.9200, keywords:['danau ranau','ranau'] },

    // ══════════════════════════════════════════════════════════
    // PALEMBANG DETAIL — DARI AMPERA KE SELURUH WILAYAH KOTA
    // ══════════════════════════════════════════════════════════

    // --- AREA AMPERA & SEKITARNYA ---
    { title:'Jembatan Ampera', subtitle:'Sungai Musi, Palembang', lat:-2.9917, lng:104.7634, keywords:['ampera','jembatan ampera','sungai musi'] },
    { title:'Benteng Kuto Besak', subtitle:'Jl. Sultan Mahmud Badaruddin, 19 Ilir, Palembang', lat:-2.9881, lng:104.7620, keywords:['benteng kuto besak','bkb','alun alun palembang'] },
    { title:'Masjid Agung Sultan Mahmud Badaruddin I', subtitle:'19 Ilir, Bukit Kecil, Palembang', lat:-2.9882, lng:104.7601, keywords:['masjid agung palembang','masjid agung smb'] },
    { title:'Museum Sultan Mahmud Badaruddin II', subtitle:'Jl. Sultan Mahmud Badaruddin, Palembang', lat:-2.9876, lng:104.7625, keywords:['museum smb','museum palembang'] },
    { title:'Monumen Perjuangan Rakyat (Monpera)', subtitle:'Jl. Merdeka, Palembang', lat:-2.9860, lng:104.7590, keywords:['monpera','monumen palembang'] },
    { title:'Pasar 16 Ilir', subtitle:'Jl. Jend. Sudirman, 16 Ilir, Palembang', lat:-2.9800, lng:104.7590, keywords:['pasar 16 ilir','pasar 16','16 ilir'] },
    { title:'Pasar Kuto', subtitle:'Jl. Pasar Kuto, 22 Ilir, Palembang', lat:-2.9870, lng:104.7570, keywords:['pasar kuto','kuto'] },
    { title:'Pasar Sekanak', subtitle:'Sekanak, Ilir Barat II, Palembang', lat:-2.9900, lng:104.7490, keywords:['pasar sekanak','sekanak'] },
    { title:'Jl. Merdeka Palembang', subtitle:'Ilir Barat I, Palembang', lat:-2.9860, lng:104.7570, keywords:['jalan merdeka','jl merdeka palembang'] },
    { title:'Jl. Sudirman Palembang (Simpang Polda)', subtitle:'Jl. Jend. Sudirman, Kemuning, Palembang', lat:-2.9720, lng:104.7520, keywords:['simpang polda','sudirman polda'] },
    { title:'Jl. Sudirman Palembang (Depan RSMH)', subtitle:'Jl. Jend. Sudirman Km 3.5, Kemuning', lat:-2.9690, lng:104.7510, keywords:['rsmh','rumah sakit hoesin','sudirman rsmh'] },
    { title:'Jl. Sudirman Palembang (Simpang Charitas)', subtitle:'Jl. Jend. Sudirman, Ilir Timur II', lat:-2.9665, lng:104.7530, keywords:['simpang charitas','rs charitas palembang'] },
    { title:'RS Charitas Palembang', subtitle:'Jl. Jend. Sudirman, Ilir Timur II, Palembang', lat:-2.9658, lng:104.7531, keywords:['charitas','rs charitas'] },
    { title:'RSUP Dr. Mohammad Hoesin (RSMH)', subtitle:'Jl. Jend. Sudirman Km 3.5, Kemuning, Palembang', lat:-2.9688, lng:104.7511, keywords:['rsmh','rs hoesin','rumah sakit moh hoesin'] },
    { title:'RS Pusri Palembang', subtitle:'Jl. Mayor Zen, Sei Selayur, Kalidoni, Palembang', lat:-2.9442, lng:104.7858, keywords:['rs pusri','pusri'] },
    { title:'RS Bhayangkara Palembang', subtitle:'Jl. Jend. Sudirman Km 4.5, Palembang', lat:-2.9632, lng:104.7549, keywords:['rs bhayangkara','bhayangkara'] },

    // --- ILIR BARAT I ---
    { title:'Jl. Demang Lebar Daun Palembang', subtitle:'Lorok Pakjo, Ilir Barat I, Palembang', lat:-2.9682, lng:104.7388, keywords:['demang lebar daun','jl demang'] },
    { title:'Jl. Angkatan 45 Palembang', subtitle:'Lorok Pakjo, Ilir Barat I, Palembang', lat:-2.9771, lng:104.7421, keywords:['angkatan 45'] },
    { title:'Palembang Square Mall (PS Mall)', subtitle:'Jl. Angkatan 45, Lorok Pakjo, Ilir Barat I', lat:-2.9772, lng:104.7431, keywords:['ps mall','palembang square','ps indah'] },
    { title:'Perumahan Puri Demang', subtitle:'Jl. Demang Lebar Daun, Lorok Pakjo, Palembang', lat:-2.9685, lng:104.7395, keywords:['puri demang','perumahan puri demang'] },
    { title:'Kelurahan Lorok Pakjo', subtitle:'Lorok Pakjo, Ilir Barat I, Palembang', lat:-2.9750, lng:104.7380, keywords:['lorok pakjo','pakjo'] },
    { title:'Kelurahan Bukit Lama', subtitle:'Bukit Lama, Ilir Barat I, Palembang', lat:-2.9840, lng:104.7310, keywords:['bukit lama','ilir barat bukit lama'] },
    { title:'Jl. Srijaya Negara Palembang', subtitle:'Bukit Lama, Ilir Barat I, Palembang', lat:-2.9840, lng:104.7320, keywords:['srijaya negara','jl srijaya'] },
    { title:'Universitas Sriwijaya Kampus Bukit', subtitle:'Jl. Srijaya Negara, Bukit Lama', lat:-2.9843, lng:104.7322, keywords:['unsri bukit','kampus bukit unsri'] },
    { title:'Kelurahan Bukit Baru', subtitle:'Bukit Baru, Ilir Barat I, Palembang', lat:-2.9650, lng:104.7330, keywords:['bukit baru palembang'] },
    { title:'Perumahan Parameswara', subtitle:'Jl. Parameswara, Bukit Baru, Ilir Barat I', lat:-2.9630, lng:104.7330, keywords:['parameswara','perumahan parameswara'] },
    { title:'Perumahan Kencana Indah Palembang', subtitle:'Jl. Macan Lindungan, Bukit Baru, Ilir Barat I', lat:-2.9650, lng:104.7350, keywords:['kencana indah','perumahan kencana indah'] },
    { title:'Perumahan Bukit Sangkal', subtitle:'Bukit Baru, Ilir Barat I, Palembang', lat:-2.9615, lng:104.7295, keywords:['bukit sangkal','perumahan bukit sangkal'] },

    // --- ILIR BARAT II ---
    { title:'Kelurahan Gandus', subtitle:'Gandus, Ilir Barat II, Palembang', lat:-2.9975, lng:104.7190, keywords:['gandus','kecamatan gandus'] },
    { title:'Jl. Kol. H. Burlian Palembang', subtitle:'Sukarami, Palembang', lat:-2.9415, lng:104.7055, keywords:['kol burlian','jl burlian','jalan burlian'] },
    { title:'Perumahan Bukit Sejahtera / Poligon', subtitle:'Jl. Bukit Sejahtera, Gandus, Palembang', lat:-2.9970, lng:104.7210, keywords:['poligon','bukit sejahtera','perumahan poligon'] },
    { title:'Kelurahan 32 Ilir', subtitle:'32 Ilir, Ilir Barat II, Palembang', lat:-2.9900, lng:104.7460, keywords:['32 ilir'] },
    { title:'Kelurahan Kemang Manis', subtitle:'Kemang Manis, Ilir Barat II, Palembang', lat:-2.9960, lng:104.7390, keywords:['kemang manis'] },

    // --- KEMUNING ---
    { title:'Kelurahan Ario Kemuning', subtitle:'Kemuning, Palembang', lat:-2.9680, lng:104.7480, keywords:['ario kemuning','kemuning palembang'] },
    { title:'Jl. Basuki Rahmat Palembang', subtitle:'Kemuning / Ilir Timur I, Palembang', lat:-2.9706, lng:104.7530, keywords:['basuki rahmat','jl basuki rahmat'] },
    { title:'Jl. Mayor Salim Batubara', subtitle:'9 Ilir, Ilir Timur I, Palembang', lat:-2.9723, lng:104.7602, keywords:['salim batubara','mayor salim batubara'] },

    // --- ILIR TIMUR I ---
    { title:'Kelurahan 9 Ilir', subtitle:'9 Ilir, Ilir Timur I, Palembang', lat:-2.9700, lng:104.7600, keywords:['9 ilir'] },
    { title:'Kelurahan 10 Ilir', subtitle:'10 Ilir, Ilir Timur I, Palembang', lat:-2.9710, lng:104.7630, keywords:['10 ilir'] },
    { title:'Lotte Mart Palembang', subtitle:'Jl. Basuki Rahmat, 9 Ilir, Ilir Timur I', lat:-2.9700, lng:104.7550, keywords:['lotte mart','lotte palembang'] },
    { title:'Jl. Kapten A. Rivai', subtitle:'Bukit Kecil, Palembang', lat:-2.9803, lng:104.7502, keywords:['kapten rivai','a rivai','jl rivai'] },
    { title:'Internasional Plaza (IP Mall)', subtitle:'Jl. Kapten A. Rivai, Bukit Kecil, Palembang', lat:-2.9803, lng:104.7502, keywords:['ip mall','internasional plaza','plaza palembang'] },

    // --- ILIR TIMUR II ---
    { title:'Kelurahan 8 Ilir', subtitle:'8 Ilir, Ilir Timur II, Palembang', lat:-2.9640, lng:104.7620, keywords:['8 ilir'] },
    { title:'Palembang Trade Center (PTC Mall)', subtitle:'Jl. R. Sukamto, 8 Ilir, Ilir Timur II', lat:-2.9645, lng:104.7645, keywords:['ptc mall','ptc','palembang trade center'] },
    { title:'Jl. R. Sukamto Palembang', subtitle:'8 Ilir, Ilir Timur II, Palembang', lat:-2.9643, lng:104.7625, keywords:['r sukamto','jl sukamto','sukamto palembang'] },
    { title:'Kelurahan 5 Ilir', subtitle:'5 Ilir, Ilir Timur II, Palembang', lat:-2.9600, lng:104.7700, keywords:['5 ilir'] },
    { title:'Kelurahan 4 Ilir', subtitle:'4 Ilir, Ilir Timur II, Palembang', lat:-2.9570, lng:104.7720, keywords:['4 ilir'] },
    { title:'Kelurahan 3 Ilir', subtitle:'3 Ilir, Ilir Timur II, Palembang', lat:-2.9560, lng:104.7750, keywords:['3 ilir'] },
    { title:'Kelurahan 2 Ilir', subtitle:'2 Ilir, Ilir Timur II, Palembang', lat:-2.9540, lng:104.7770, keywords:['2 ilir'] },
    { title:'Kelurahan 1 Ilir', subtitle:'1 Ilir, Ilir Timur II, Palembang', lat:-2.9520, lng:104.7790, keywords:['1 ilir'] },

    // --- ILIR TIMUR III ---
    { title:'Kelurahan Sungai Selincah', subtitle:'Sungai Selincah, Ilir Timur III, Palembang', lat:-2.9480, lng:104.7850, keywords:['sungai selincah'] },
    { title:'Kelurahan Sei Selincah', subtitle:'Sei Selincah, Kalidoni, Palembang', lat:-2.9470, lng:104.7840, keywords:['sei selincah kalidoni'] },
    { title:'Kelurahan Sungai Lais', subtitle:'Sungai Lais, Kalidoni, Palembang', lat:-2.9500, lng:104.7900, keywords:['sungai lais','sei lais'] },

    // --- KALIDONI ---
    { title:'Kecamatan Kalidoni', subtitle:'Kalidoni, Palembang', lat:-2.9500, lng:104.7850, keywords:['kalidoni','kecamatan kalidoni'] },
    { title:'Perumahan Kalidoni Permai', subtitle:'Kalidoni, Palembang', lat:-2.9500, lng:104.7850, keywords:['kalidoni permai','perumahan kalidoni permai'] },
    { title:'RS Pusri / Perumahan Pusri', subtitle:'Jl. Mayor Zen, Sei Selayur, Kalidoni', lat:-2.9440, lng:104.7862, keywords:['perumahan pusri','griya pusri'] },
    { title:'Jl. Mayor Zen Palembang', subtitle:'Kalidoni, Palembang', lat:-2.9450, lng:104.7830, keywords:['mayor zen','jl mayor zen'] },
    { title:'Kelurahan Sei Selayur', subtitle:'Sei Selayur, Kalidoni, Palembang', lat:-2.9470, lng:104.8055, keywords:['sei selayur','sungai selayur'] },

    // --- SAKO (PALEMBANG) ---
    { title:'Kecamatan Sako Palembang', subtitle:'Sako, Palembang', lat:-2.9200, lng:104.7800, keywords:['sako palembang','kecamatan sako'] },
    { title:'Kelurahan Sako Palembang', subtitle:'Sako, Kecamatan Sako, Palembang', lat:-2.9200, lng:104.7800, keywords:['kelurahan sako','sako baru'] },
    { title:'Kelurahan Sialang', subtitle:'Sialang, Sako, Palembang', lat:-2.9150, lng:104.7880, keywords:['sialang','sialang sako'] },
    { title:'Kelurahan Srijaya', subtitle:'Srijaya, Sako, Palembang', lat:-2.9230, lng:104.7750, keywords:['srijaya sako','kelurahan srijaya'] },
    { title:'Kelurahan Sukamaju', subtitle:'Sukamaju, Sako, Palembang', lat:-2.9180, lng:104.7820, keywords:['sukamaju sako'] },
    { title:'Perumahan Taman Kenten', subtitle:'Jl. Lettu Kahar Muzakir, Sako, Palembang', lat:-2.9240, lng:104.7780, keywords:['taman kenten','kenten palembang'] },
    { title:'Perumahan Sako Kenten', subtitle:'Sako, Palembang', lat:-2.9180, lng:104.7830, keywords:['sako kenten','perumahan sako kenten'] },
    { title:'SD Negeri 199 Palembang', subtitle:'Sako, Palembang', lat:-2.9210, lng:104.7795, keywords:['sd 199 sako','sekolah sako'] },
    { title:'Masjid Al-Ittihad Sako', subtitle:'Sako, Palembang', lat:-2.9195, lng:104.7805, keywords:['masjid al ittihad sako'] },
    { title:'Pasar Sako', subtitle:'Sako, Palembang', lat:-2.9205, lng:104.7810, keywords:['pasar sako','pasar tradisional sako'] },

    // --- SEMATANG BORANG ---
    { title:'Kecamatan Sematang Borang', subtitle:'Sematang Borang, Palembang', lat:-2.9350, lng:104.6990, keywords:['sematang borang','sematang'] },
    { title:'Kelurahan Sematang Borang', subtitle:'Sematang Borang, Palembang', lat:-2.9350, lng:104.6990, keywords:['kelurahan sematang borang'] },
    { title:'Kelurahan Lebung Gajah', subtitle:'Lebung Gajah, Sematang Borang, Palembang', lat:-2.9390, lng:104.6940, keywords:['lebung gajah'] },
    { title:'Kelurahan Srimulya', subtitle:'Srimulya, Sematang Borang, Palembang', lat:-2.9310, lng:104.7020, keywords:['srimulya','kelurahan srimulya'] },
    { title:'Kelurahan Srijaya Palembang', subtitle:'Srijaya, Sematang Borang, Palembang', lat:-2.9380, lng:104.7050, keywords:['srijaya sematang'] },
    { title:'Perumahan Griya Musi Permai', subtitle:'Sematang Borang, Palembang', lat:-2.9300, lng:104.6900, keywords:['griya musi','griya musi permai'] },
    { title:'Perumahan Bukit Berlian', subtitle:'Sematang Borang, Palembang', lat:-2.9380, lng:104.6980, keywords:['bukit berlian sematang'] },
    { title:'Jl. Tanjung Api-Api Palembang', subtitle:'Sematang Borang / Sukarami, Palembang', lat:-2.9305, lng:104.6810, keywords:['tanjung api api','jl tanjung api api'] },
    { title:'Pelaminan Family Zainal (Gudang Utama)', subtitle:'Sematang Borang / Sako, Palembang 30161', lat:-2.9389551, lng:104.8106462, keywords:['pelaminan family','pelaminan zainal','gudang pelaminan','toko pelaminan'] },

    // --- SUKARAMI ---
    { title:'Kecamatan Sukarami', subtitle:'Sukarami, Palembang', lat:-2.9350, lng:104.7100, keywords:['sukarami','kecamatan sukarami'] },
    { title:'Kelurahan Sukarami', subtitle:'Sukarami, Palembang', lat:-2.9350, lng:104.7100, keywords:['kelurahan sukarami'] },
    { title:'Kelurahan Kebun Bunga', subtitle:'Kebun Bunga, Sukarami, Palembang', lat:-2.9400, lng:104.7150, keywords:['kebun bunga palembang','kebun bunga sukarami'] },
    { title:'Kelurahan Sukajaya', subtitle:'Sukajaya, Sukarami, Palembang', lat:-2.9280, lng:104.7080, keywords:['sukajaya sukarami'] },
    { title:'Kelurahan Talang Betutu', subtitle:'Talang Betutu, Sukarami, Palembang', lat:-2.8980, lng:104.7010, keywords:['talang betutu'] },
    { title:'Bandara Sultan Mahmud Badaruddin II', subtitle:'Talang Betutu, Sukarami, Palembang', lat:-2.8972, lng:104.7010, keywords:['bandara palembang','bandara smb','smb2','airport palembang'] },
    { title:'Kelurahan Sukabangun', subtitle:'Sukabangun, Sukarami, Palembang', lat:-2.9420, lng:104.7050, keywords:['sukabangun'] },
    { title:'Jl. Lingkaran Selatan / Lingkar Selatan Palembang', subtitle:'Sukarami, Palembang', lat:-2.9450, lng:104.7100, keywords:['lingkaran selatan','lingkar selatan','jl lingkar selatan'] },

    // --- ALANG-ALANG LEBAR ---
    { title:'Kecamatan Alang-Alang Lebar', subtitle:'Alang-Alang Lebar, Palembang', lat:-2.9500, lng:104.7150, keywords:['alang alang lebar','aal','kecamatan alang alang lebar'] },
    { title:'Kelurahan Alang-Alang Lebar', subtitle:'Alang-Alang Lebar, Palembang', lat:-2.9500, lng:104.7150, keywords:['kelurahan alang alang lebar'] },
    { title:'Kelurahan Talang Kelapa Palembang', subtitle:'Talang Kelapa, Alang-Alang Lebar, Palembang', lat:-2.9520, lng:104.7100, keywords:['talang kelapa palembang','perumahan talang kelapa'] },
    { title:'Kelurahan Karya Baru', subtitle:'Karya Baru, Alang-Alang Lebar, Palembang', lat:-2.9480, lng:104.7200, keywords:['karya baru aal','karya baru palembang'] },
    { title:'Perumahan Charindoland', subtitle:'Alang-Alang Lebar, Palembang', lat:-2.9480, lng:104.7160, keywords:['charindo','charindoland'] },
    { title:'Perumahan Griya Candra', subtitle:'Jl. Srijaya Negara, Alang-Alang Lebar', lat:-2.9580, lng:104.7250, keywords:['griya candra','candra indah'] },

    // --- JAKABARING ---
    { title:'Jakabaring Sport City (JSC)', subtitle:'Jl. Gubernur H. Bastari, Jakabaring, Palembang', lat:-3.0185, lng:104.7890, keywords:['jsc','jakabaring sport city','stadion jakabaring'] },
    { title:'OPI Mall Jakabaring', subtitle:'Jl. Gubernur H. Bastari, Jakabaring, Palembang', lat:-3.0215, lng:104.7925, keywords:['opi mall','opi jakabaring','mal opi'] },
    { title:'Perumahan OPI Jakabaring', subtitle:'Komplek OPI, Jakabaring, Palembang', lat:-3.0210, lng:104.7920, keywords:['perumahan opi','opi regency','komplek opi jakabaring'] },
    { title:'Danau OPI Jakabaring', subtitle:'Jakabaring, Palembang', lat:-3.0250, lng:104.7900, keywords:['danau opi','wisata danau opi'] },
    { title:'Kelurahan Silaberanti', subtitle:'Silaberanti, Jakabaring, Palembang', lat:-2.9945, lng:104.7755, keywords:['silaberanti'] },
    { title:'Kelurahan 15 Ulu', subtitle:'15 Ulu, Seberang Ulu I, Palembang', lat:-3.0490, lng:104.7695, keywords:['15 ulu','kertapati area'] },
    { title:'Kelurahan 13 Ulu', subtitle:'13 Ulu, Seberang Ulu I, Palembang', lat:-2.9870, lng:104.7734, keywords:['13 ulu'] },

    // --- KERTAPATI ---
    { title:'Kecamatan Kertapati', subtitle:'Kertapati, Palembang', lat:-3.0080, lng:104.7600, keywords:['kertapati','kecamatan kertapati'] },
    { title:'Stasiun KA Kertapati', subtitle:'Kemas Rindo, Kertapati, Palembang', lat:-3.0080, lng:104.7580, keywords:['stasiun kertapati','stasiun palembang'] },
    { title:'Kelurahan Kemas Rindo', subtitle:'Kemas Rindo, Kertapati, Palembang', lat:-3.0155, lng:104.7530, keywords:['kemas rindo'] },
    { title:'Kelurahan Ogan Baru', subtitle:'Ogan Baru, Kertapati, Palembang', lat:-3.0235, lng:104.7555, keywords:['ogan baru'] },

    // --- PLAJU ---
    { title:'Kecamatan Plaju', subtitle:'Plaju, Palembang', lat:-2.9980, lng:104.7950, keywords:['plaju','kecamatan plaju'] },
    { title:'Kelurahan Plaju Ulu', subtitle:'Plaju Ulu, Plaju, Palembang', lat:-2.9980, lng:104.7950, keywords:['plaju ulu'] },
    { title:'Kelurahan Plaju Darat', subtitle:'Plaju Darat, Plaju, Palembang', lat:-3.0150, lng:104.8150, keywords:['plaju darat'] },
    { title:'Kelurahan Plaju Ilir', subtitle:'Plaju Ilir, Plaju, Palembang', lat:-2.9940, lng:104.8010, keywords:['plaju ilir'] },
    { title:'Kelurahan Bagus Kuning', subtitle:'Bagus Kuning, Plaju, Palembang', lat:-2.9900, lng:104.7900, keywords:['bagus kuning'] },
    { title:'Kelurahan Talang Bubuk', subtitle:'Talang Bubuk, Plaju, Palembang', lat:-3.0050, lng:104.8000, keywords:['talang bubuk'] },
    { title:'Komplek Pertamina Plaju', subtitle:'Plaju, Palembang', lat:-2.9950, lng:104.8050, keywords:['komplek pertamina plaju','pertamina plaju','perumahan pertamina'] },
    { title:'Perumahan Taman Sasana Patra', subtitle:'Plaju Darat, Plaju, Palembang', lat:-3.0200, lng:104.8250, keywords:['sasana patra','taman sasana patra'] },

    // ══════════════════════════════════════════════════════════
    // SAKO (Kec. RAMBUTAN, KAB. BANYUASIN) — DETAIL LENGKAP
    // Area sekitar: https://maps.app.goo.gl/UpqJTEPrDaqyrPWe6
    // Koordinat pusat: -3.073, 104.873
    // ══════════════════════════════════════════════════════════
    { title:'Desa Sako Rambutan (Pusat)', subtitle:'Sako, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.0730, lng:104.8734, keywords:['sako rambutan','desa sako','sako banyuasin'] },
    { title:'Masjid Baiturohim Sako Rambutan', subtitle:'Sako, Rambutan, Banyuasin', lat:-3.0742, lng:104.8749, keywords:['masjid baiturohim sako','masjid sako rambutan'] },
    { title:'Dusun I Desa Sako', subtitle:'Desa Sako, Rambutan, Banyuasin', lat:-3.0715, lng:104.8710, keywords:['dusun 1 sako','dusun i sako'] },
    { title:'Dusun II Desa Sako', subtitle:'Desa Sako, Rambutan, Banyuasin', lat:-3.0745, lng:104.8750, keywords:['dusun 2 sako','dusun ii sako'] },
    { title:'Dusun III Desa Sako', subtitle:'Desa Sako, Rambutan, Banyuasin', lat:-3.0760, lng:104.8780, keywords:['dusun 3 sako','dusun iii sako'] },
    { title:'Jl. Raya Sako Rambutan', subtitle:'Sako, Rambutan, Banyuasin, Sumatera Selatan', lat:-3.0735, lng:104.8720, keywords:['jalan raya sako','jl raya sako rambutan'] },
    { title:'Toko Nia Sako Rambutan', subtitle:'Sako, Rambutan, Banyuasin 30967', lat:-3.0730, lng:104.8734, keywords:['toko nia sako','nia sako'] },
    { title:'SD Negeri Sako Rambutan', subtitle:'Sako, Rambutan, Banyuasin', lat:-3.0722, lng:104.8740, keywords:['sd sako rambutan','sekolah sako'] },
    { title:'Masjid Al-Ikhlas Sako Rambutan', subtitle:'Sako, Rambutan, Banyuasin', lat:-3.0728, lng:104.8729, keywords:['masjid al ikhlas sako','masjid sako'] },
    { title:'Pasar Sako Rambutan', subtitle:'Sako, Rambutan, Banyuasin', lat:-3.0718, lng:104.8725, keywords:['pasar sako rambutan'] },
    { title:'Perumahan Musi Palem Indah', subtitle:'Sungai Pinang, Rambutan, Banyuasin 30762', lat:-3.0408, lng:104.8385, keywords:['musi palem indah','perumahan musi palem','palem indah'] },
    { title:'Perumahan Musi Palem Indah Blok A', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.0402, lng:104.8380, keywords:['musi palem blok a','palem indah blok a'] },
    { title:'Perumahan Musi Palem Indah Blok B', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.0405, lng:104.8383, keywords:['musi palem blok b','palem indah blok b'] },
    { title:'Perumahan Musi Palem Indah Blok C', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.0409, lng:104.8387, keywords:['musi palem blok c','palem indah blok c'] },
    { title:'Perumahan Musi Palem Indah Blok D', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.0412, lng:104.8390, keywords:['musi palem blok d','palem indah blok d'] },
    { title:'Desa Sungai Pinang (Rambutan)', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.0420, lng:104.8400, keywords:['sungai pinang rambutan','desa sungai pinang'] },
    { title:'Jl. Sungai Pinang Rambutan', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.0406, lng:104.8378, keywords:['jalan sungai pinang','jl sungai pinang rambutan'] },
    { title:'Masjid Jami Sungai Pinang', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.0415, lng:104.8392, keywords:['masjid jami sungai pinang'] },
    { title:'Perumahan Sungai Pinang Indah', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.0440, lng:104.8420, keywords:['sungai pinang indah','perumahan sungai pinang indah'] },
    { title:'Perumahan Griya Sungai Pinang', subtitle:'Sungai Pinang, Rambutan, Banyuasin', lat:-3.0460, lng:104.8450, keywords:['griya sungai pinang'] },
    { title:'Desa Sungai Dua Rambutan', subtitle:'Sungai Dua, Rambutan, Banyuasin', lat:-3.0570, lng:104.8635, keywords:['sungai dua rambutan','desa sungai dua'] },
    { title:'Kantor Desa Sungai Dua', subtitle:'Sungai Dua, Rambutan, Banyuasin', lat:-3.0570, lng:104.8634, keywords:['kantor desa sungai dua'] },
    { title:'Masjid Taufiqurrahman Sungai Dua', subtitle:'Sungai Dua, Rambutan, Banyuasin', lat:-3.0571, lng:104.8629, keywords:['masjid taufiqurrahman sungai dua'] },
    { title:'Masjid Al-Muttaqin Sungai Dua', subtitle:'Sungai Dua, Rambutan, Banyuasin', lat:-3.0541, lng:104.8632, keywords:['masjid al muttaqin sungai dua'] },
    { title:'Desa Gelebak Dalam Rambutan', subtitle:'Gelebak Dalam, Rambutan, Banyuasin', lat:-3.0855, lng:104.8653, keywords:['gelebak dalam rambutan','desa gelebak dalam'] },
    { title:'Pangkalan Gelebak', subtitle:'Pangkalan Gelebak, Rambutan, Banyuasin', lat:-3.0659, lng:104.8625, keywords:['pangkalan gelebak'] },
    { title:'Masjid Pembela Agung Alhusna Gelebak', subtitle:'Pangkalan Gelebak, Rambutan, Banyuasin', lat:-3.0671, lng:104.8624, keywords:['masjid alhusna gelebak','masjid pembela agung'] },
    { title:'Desa Menten Rambutan', subtitle:'Menten, Rambutan, Banyuasin', lat:-3.0587, lng:104.8892, keywords:['menten rambutan','desa menten'] },
    { title:'Desa Suko Pindah Rambutan', subtitle:'Suka Pindah, Rambutan, Banyuasin', lat:-3.0375, lng:104.9700, keywords:['suka pindah rambutan','suko pindah'] },
    { title:'Desa Kebun Sahang Rambutan', subtitle:'Kebun Sahang, Rambutan, Banyuasin', lat:-3.1175, lng:104.9999, keywords:['kebun sahang rambutan','kebon sahang'] },
    { title:'Desa Rambutan (Ibukota Kecamatan)', subtitle:'Rambutan, Banyuasin', lat:-3.1292, lng:104.9326, keywords:['desa rambutan','ibukota rambutan'] },
    { title:'Desa Tanah Lembak Rambutan', subtitle:'Tanah Lembak, Rambutan, Banyuasin', lat:-3.1385, lng:104.9699, keywords:['tanah lembak rambutan'] },
    { title:'Desa Durian Gadis Rambutan', subtitle:'Durian Gadis, Rambutan, Banyuasin', lat:-3.0285, lng:104.9495, keywords:['durian gadis rambutan'] },
    { title:'Desa Tanjungan Rambutan', subtitle:'Tanjungan, Rambutan, Banyuasin', lat:-3.1400, lng:104.8800, keywords:['tanjungan rambutan','desa tanjungan'] },
    { title:'Desa Tanjung Merbu Rambutan', subtitle:'Tanjung Merbu, Rambutan, Banyuasin', lat:-3.0857, lng:104.9017, keywords:['tanjung merbu rambutan'] },
    { title:'Desa Suko Vokasi Rambutan', subtitle:'Suko Vokasi, Rambutan, Banyuasin', lat:-3.0950, lng:104.8650, keywords:['suko vokasi','suka vokasi rambutan'] },
    { title:'Desa Dusun Baru Rambutan', subtitle:'Dusun Baru, Rambutan, Banyuasin', lat:-3.0024, lng:104.9785, keywords:['dusun baru rambutan','desa baru rambutan'] },
    { title:'Desa Sako Suban Rambutan', subtitle:'Sako Suban, Rambutan, Banyuasin', lat:-3.0850, lng:104.8800, keywords:['sako suban rambutan'] },
    { title:'Desa Parit Rambutan', subtitle:'Parit, Rambutan, Banyuasin', lat:-3.0624, lng:104.9535, keywords:['desa parit rambutan','parit rambutan'] },
    { title:'Desa Simpang Empat Rambutan', subtitle:'Simpang Empat, Rambutan, Banyuasin', lat:-3.1554, lng:104.8289, keywords:['simpang empat rambutan'] },
    { title:'Desa Pelaju Rambutan', subtitle:'Pelaju, Rambutan, Banyuasin', lat:-3.0563, lng:104.9818, keywords:['pelaju rambutan','desa pelaju'] },
    { title:'Desa Terusan Jawa Rambutan', subtitle:'Terusan Jawa, Rambutan, Banyuasin', lat:-3.1638, lng:104.8521, keywords:['terusan jawa rambutan'] },
    { title:'Desa Muara Batun Rambutan', subtitle:'Muara Batun, Rambutan, Banyuasin', lat:-3.1841, lng:104.8469, keywords:['muara batun rambutan'] },
    { title:'Desa Sungai Rasau Rambutan', subtitle:'Sungai Rasau, Rambutan, Banyuasin', lat:-3.0889, lng:104.8267, keywords:['sungai rasau rambutan'] },
    { title:'Perumahan Rambutan Asri', subtitle:'Sungai Kedukan / Rambutan, Banyuasin', lat:-3.0500, lng:104.8350, keywords:['rambutan asri','perumahan rambutan asri'] },
    { title:'Perumahan Graha Sungai Kedukan', subtitle:'Sungai Kedukan, Rambutan, Banyuasin', lat:-3.0520, lng:104.8300, keywords:['graha sungai kedukan'] },
    { title:'Desa Sungai Kedukan Rambutan', subtitle:'Sungai Kedukan, Rambutan, Banyuasin', lat:-3.0550, lng:104.8250, keywords:['sungai kedukan rambutan','desa sungai kedukan'] },
    { title:'Perumahan OPI Indah Sungai Kedukan', subtitle:'Sungai Kedukan, Rambutan, Banyuasin', lat:-3.0450, lng:104.8100, keywords:['opi indah sungai kedukan','perumahan opi indah'] }
  ];


  // ============================================================
  // CACHE (5 menit TTL)
  // ============================================================
  var searchCache = new Map();
  var CACHE_TTL = 5 * 60 * 1000;

  function getCached(key) {
    var e = searchCache.get(key);
    if (!e) return null;
    if (Date.now() - e.time > CACHE_TTL) { searchCache.delete(key); return null; }
    return e.data;
  }
  function setCache(key, data) { searchCache.set(key, { data: data, time: Date.now() }); }

  // ============================================================
  // PREMIUM OUT-OF-COVERAGE MODAL
  // ============================================================
  function buildCoverageModal() {
    var existing = document.getElementById('pfModal_OutOfCoverage');
    if (existing) return existing;

    var style = document.createElement('style');
    style.textContent = [
      '#pfModal_OutOfCoverage{display:none;position:fixed;inset:0;z-index:9999999;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,0);transition:background .3s;}',
      '#pfModal_OutOfCoverage.is-open{display:flex;background:rgba(10,10,20,0.78);backdrop-filter:blur(10px);}',
      '.pfModal__card{background:#fff;border-radius:24px;max-width:460px;width:100%;overflow:hidden;transform:translateY(40px) scale(.94);opacity:0;transition:transform .42s cubic-bezier(.34,1.56,.64,1),opacity .36s;box-shadow:0 32px 80px rgba(0,0,0,.28);}',
      '#pfModal_OutOfCoverage.is-open .pfModal__card{transform:translateY(0) scale(1);opacity:1;}',
      '.pfModal__header{background:linear-gradient(135deg,#c0392b 0%,#922b21 100%);padding:28px 24px 22px;text-align:center;position:relative;overflow:hidden;}',
      '.pfModal__pulse{width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;position:relative;}',
      '.pfModal__pulse::before,.pfModal__pulse::after{content:"";position:absolute;inset:-8px;border-radius:50%;border:2.5px solid rgba(255,255,255,.3);animation:pfPulse 2s ease-out infinite;}',
      '.pfModal__pulse::after{inset:-18px;border-color:rgba(255,255,255,.15);animation-delay:.65s;}',
      '@keyframes pfPulse{0%{transform:scale(.9);opacity:1}100%{transform:scale(1.5);opacity:0}}',
      '.pfModal__icon{font-size:34px;line-height:1;}',
      '.pfModal__htitle{color:#fff;font-size:18px;font-weight:800;margin:0;letter-spacing:.3px;}',
      '.pfModal__hsubtitle{color:rgba(255,255,255,.78);font-size:13px;margin:5px 0 0;}',
      '.pfModal__body{padding:20px 22px 0;}',
      '.pfModal__zonebadge{display:flex;align-items:flex-start;gap:10px;background:#fff9f0;border:1.5px solid #f39c12;border-radius:14px;padding:12px 14px;margin-bottom:14px;}',
      '.pfModal__zonebadge-icon{font-size:22px;flex-shrink:0;margin-top:1px;}',
      '.pfModal__zonebadge-text{font-size:12.5px;color:#7d6608;line-height:1.55;}',
      '.pfModal__zonebadge-text strong{color:#6e5500;display:block;font-size:13.5px;margin-bottom:2px;}',
      '.pfModal__coverageTitle{font-size:11.5px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.8px;margin:0 0 10px;}',
      '.pfModal__chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:18px;max-height:130px;overflow-y:auto;}',
      '.pfModal__chip{background:#f0f9f4;border:1.5px solid #27ae60;border-radius:20px;padding:4px 11px;font-size:11.5px;color:#1a7a42;font-weight:600;}',
      '.pfModal__chip::before{content:"✓ ";font-weight:900;}',
      '.pfModal__footer{padding:0 22px 20px;display:flex;flex-direction:column;gap:9px;}',
      '.pfModal__btnPrimary{background:linear-gradient(135deg,#27ae60,#1e8449);color:#fff;border:none;border-radius:14px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .2s,transform .15s;width:100%;}',
      '.pfModal__btnPrimary:hover{opacity:.91;transform:translateY(-1px);}',
      '.pfModal__btnWa{background:linear-gradient(135deg,#25d366,#128c7e);color:#fff;border:none;border-radius:14px;padding:11px;font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;width:100%;text-decoration:none;}',
      '.pfModal__btnWa:hover{opacity:.9;}',
      '.pfModal__btnSecondary{background:#f5f5f5;color:#555;border:none;border-radius:14px;padding:11px;font-size:13px;font-weight:600;cursor:pointer;width:100%;}',
      '.pfModal__btnSecondary:hover{background:#eee;}'
    ].join('');
    document.head.appendChild(style);

    var modal = document.createElement('div');
    modal.id = 'pfModal_OutOfCoverage';
    modal.innerHTML =
      '<div class="pfModal__card">' +
        '<div class="pfModal__header">' +
          '<div class="pfModal__pulse"><div class="pfModal__icon">🚫</div></div>' +
          '<h3 class="pfModal__htitle">Di Luar Jangkauan Pengiriman</h3>' +
          '<p class="pfModal__hsubtitle">Layanan armada khusus Provinsi Sumatera Selatan</p>' +
        '</div>' +
        '<div class="pfModal__body">' +
          '<div class="pfModal__zonebadge">' +
            '<div class="pfModal__zonebadge-icon">⚠️</div>' +
            '<div class="pfModal__zonebadge-text">' +
              '<strong>Lokasi Tidak Terjangkau</strong>' +
              'Lokasi <em id="pfModal_AreaName">yang Anda pilih</em> berada di luar wilayah layanan kami.' +
            '</div>' +
          '</div>' +
          '<p class="pfModal__coverageTitle">✅ Wilayah yang Kami Layani (17 Kab/Kota)</p>' +
          '<div class="pfModal__chips">' +
            '<span class="pfModal__chip">Kota Palembang</span>' +
            '<span class="pfModal__chip">Kota Prabumulih</span>' +
            '<span class="pfModal__chip">Kota Lubuklinggau</span>' +
            '<span class="pfModal__chip">Kota Pagar Alam</span>' +
            '<span class="pfModal__chip">Kab. Banyuasin</span>' +
            '<span class="pfModal__chip">Kab. Musi Banyuasin</span>' +
            '<span class="pfModal__chip">Kab. Musi Rawas</span>' +
            '<span class="pfModal__chip">Kab. Muratara</span>' +
            '<span class="pfModal__chip">Kab. Lahat</span>' +
            '<span class="pfModal__chip">Kab. Muara Enim</span>' +
            '<span class="pfModal__chip">Kab. Empat Lawang</span>' +
            '<span class="pfModal__chip">Kab. PALI</span>' +
            '<span class="pfModal__chip">Kab. Ogan Ilir</span>' +
            '<span class="pfModal__chip">Kab. OKI</span>' +
            '<span class="pfModal__chip">Kab. OKU</span>' +
            '<span class="pfModal__chip">Kab. OKU Timur</span>' +
            '<span class="pfModal__chip">Kab. OKU Selatan</span>' +
          '</div>' +
        '</div>' +
        '<div class="pfModal__footer">' +
          '<button type="button" class="pfModal__btnPrimary" id="pfModal_BtnPilihLagi">📍 Pilih Lokasi di Sumatera Selatan</button>' +
          '<a class="pfModal__btnWa" href="https://wa.me/6281234567890?text=Halo%20Pelaminan%20Family%2C%20saya%20ingin%20tanya%20pengiriman%20ke%20luar%20Sumsel." target="_blank" rel="noopener">' +
            '<svg width="17" height="17" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.49"/></svg>' +
            ' Hubungi via WhatsApp' +
          '</a>' +
          '<button type="button" class="pfModal__btnSecondary" id="pfModal_BtnTutup">Tutup</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(modal);

    document.getElementById('pfModal_BtnPilihLagi').addEventListener('click', function () { closeCoverageModal(); });
    document.getElementById('pfModal_BtnTutup').addEventListener('click', function () { closeCoverageModal(); });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeCoverageModal(); });
    return modal;
  }

  var _modal = null;

  function showCoverageWarning(areaName) {
    if (!_modal) _modal = buildCoverageModal();
    var el = document.getElementById('pfModal_AreaName');
    if (el) el.textContent = areaName ? '"' + areaName + '"' : 'yang Anda pilih';
    _modal.style.display = 'flex';
    requestAnimationFrame(function () { requestAnimationFrame(function () { _modal.classList.add('is-open'); }); });
  }

  function closeCoverageModal() {
    if (!_modal) return;
    _modal.classList.remove('is-open');
    setTimeout(function () { if (_modal) _modal.style.display = 'none'; }, 420);
  }

  // ============================================================
  // INIT
  // ============================================================
  document.addEventListener('DOMContentLoaded', initMapPicker);

  function initMapPicker() {
    var mapElement = document.getElementById('delivery-map');
    if (!mapElement) return;

    var latInput = document.getElementById('delivery_latitude');
    var lngInput = document.getElementById('delivery_longitude');
    var mapAddressInput = document.getElementById('delivery_map_address');
    var btnMyLocation = document.getElementById('btn-use-my-location');
    var pickupRadios = document.querySelectorAll('input[name="pickup_method"]');
    var mapContainer = document.getElementById('map-picker-container');

    var defaultLat = -2.9909340, defaultLng = 104.7565540;
    var lastValidLat = parseFloat(latInput && latInput.value) || defaultLat;
    var lastValidLng = parseFloat(lngInput && lngInput.value) || defaultLng;
    var currentLat = lastValidLat, currentLng = lastValidLng;
    var hasSelectedLocation = !!(latInput && latInput.value && lngInput && lngInput.value);

    _modal = buildCoverageModal();

    // ── LEAFLET MAP ──
    var map = null, marker = null;

    var pinIcon = (typeof L !== 'undefined') ? L.divIcon({
      className: '',
      html: '<div style="position:relative;width:36px;height:48px;filter:drop-shadow(0 4px 10px rgba(0,0,0,.35));transform:translate(-50%,-100%);cursor:grab;">' +
            '<svg width="36" height="48" viewBox="0 0 36 48" fill="none" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M18 0C8.06 0 0 8.06 0 18C0 31.5 18 48 18 48C18 48 36 31.5 36 18C36 8.06 27.94 0 18 0Z" fill="#D8854E"/>' +
            '<circle cx="18" cy="18" r="9" fill="white"/><circle cx="18" cy="18" r="4.5" fill="#362217"/></svg></div>',
      iconSize: [0, 0], iconAnchor: [0, 0], popupAnchor: [0, -50]
    }) : null;

    function setupMap(attempts) {
      if (typeof L !== 'undefined') {
        try {
          if (!map) {
            map = L.map('delivery-map', { center: [currentLat, currentLng], zoom: 15, scrollWheelZoom: true });
            var osmTile = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' });
            var cartoTile = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap &copy; CARTO' });
            osmTile.on('tileerror', function () { if (map && !map.hasLayer(cartoTile)) { map.removeLayer(osmTile); map.addLayer(cartoTile); } });
            osmTile.addTo(map);
            marker = L.marker([currentLat, currentLng], { icon: pinIcon || undefined, draggable: true, title: 'Geser ke lokasi pengiriman (Sumsel)' }).addTo(map);
            marker.bindPopup('<b>📍 Titik Pengiriman</b><br>Klik peta atau geser pin ke lokasi acara.').openPopup();
            map.on('click', function (e) { updateLocation(e.latlng.lat, e.latlng.lng, true); });
            marker.on('dragend', function () { var p = marker.getLatLng(); updateLocation(p.lat, p.lng, true); });
          }
        } catch (e) { console.warn('Map init:', e); }
      } else if ((attempts || 0) < 20) { setTimeout(function () { setupMap((attempts || 0) + 1); }, 200); }
    }
    setupMap(0);

    // ── REVERSE GEOCODE ──
    // ── Haversine sederhana untuk cari landmark terdekat ──
    function haversineSimple(lat1, lon1, lat2, lon2) {
      var R = 6371000; // meter
      var dLat = (lat2 - lat1) * Math.PI / 180;
      var dLon = (lon2 - lon1) * Math.PI / 180;
      var a = Math.sin(dLat/2)*Math.sin(dLat/2) +
              Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*
              Math.sin(dLon/2)*Math.sin(dLon/2);
      return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    // ── Cari landmark terdekat dalam radius maxMeters ──
    function findNearestLandmark(lat, lng, maxMeters) {
      var best = null, bestDist = Infinity;
      LOCAL_LANDMARKS.forEach(function(lm) {
        var d = haversineSimple(lat, lng, parseFloat(lm.lat), parseFloat(lm.lng));
        if (d < bestDist) { bestDist = d; best = lm; }
      });
      return (best && bestDist <= maxMeters) ? { landmark: best, distM: Math.round(bestDist) } : null;
    }

    function reverseGeocode(lat, lng) {
      if (mapAddressInput) mapAddressInput.placeholder = 'Membaca detail nama alamat & jalan...';

      // 1. Cek landmark terdekat dari kamus lokal Sumsel
      var nearLandmark = findNearestLandmark(lat, lng, 350) || findNearestLandmark(lat, lng, 800);

      // 2. Query ArcGIS World Geocode Server (sangat detail untuk nama jalan, toko/tempat, desa, kecamatan)
      var arcGisUrl = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/reverseGeocode?f=json&location=' + lng + ',' + lat + '&distance=1000';

      fetch(arcGisUrl)
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var a = (data && data.address) || {};
          var parts = [];

          var place = a.PlaceName || '';
          if (!place && a.Addr_type === 'POI') place = a.Match_addr || '';
          var street = a.Address || '';
          var neighborhood = a.Neighborhood || '';
          var city = a.City || '';
          var subregion = a.Subregion || '';
          var region = a.Region || '';
          var postal = a.Postal || '';

          // Jika ada landmark lokal yang sangat dekat (<250m) dan belum ada di place
          if (nearLandmark && nearLandmark.distM <= 250) {
            var lmTitle = nearLandmark.landmark.title;
            if (!place || place.toLowerCase().indexOf(lmTitle.toLowerCase().substring(0, 6)) < 0) {
              parts.push(nearLandmark.distM <= 50 ? lmTitle : ('Dekat ' + lmTitle));
            }
          }

          if (place && parts.indexOf(place) < 0 && (!street || place.toLowerCase() !== street.toLowerCase())) {
            parts.push(place);
          }

          if (street && parts.indexOf(street) < 0) {
            parts.push(street);
          }

          if (neighborhood && parts.indexOf(neighborhood) < 0) {
            parts.push(neighborhood);
          }

          if (city && parts.indexOf(city) < 0) {
            parts.push(city.toLowerCase().indexOf('kec') >= 0 ? city : ('Kec. ' + city));
          }

          if (subregion && parts.indexOf(subregion) < 0) {
            var isKota = (subregion.toLowerCase() === 'palembang' || subregion.toLowerCase() === 'prabumulih' || subregion.toLowerCase() === 'lubuklinggau' || subregion.toLowerCase() === 'pagar alam');
            parts.push(isKota ? ('Kota ' + subregion) : ('Kab. ' + subregion));
          }

          if (region && parts.indexOf(region) < 0) {
            parts.push(region);
          }

          if (postal && parts.indexOf(postal) < 0) {
            parts.push(postal);
          }

          var formattedAddress = parts.join(', ');

          if (!formattedAddress || parts.length < 2) {
            // Jika ArcGIS tidak lengkap, fallback ke Nominatim
            fallbackNominatim(lat, lng, nearLandmark);
            return;
          }

          applyAddressResult(lat, lng, formattedAddress);
        })
        .catch(function () {
          fallbackNominatim(lat, lng, nearLandmark);
        });
    }

    function fallbackNominatim(lat, lng, nearLandmark) {
      fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng + '&addressdetails=1', { headers: { 'Accept-Language': 'id,en;q=0.8' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var addr = data.address || {};
          var parts = [];

          if (nearLandmark && nearLandmark.distM <= 300) {
            parts.push(nearLandmark.distM <= 50 ? nearLandmark.landmark.title : ('Dekat ' + nearLandmark.landmark.title));
          }

          var road = addr.road || addr.street || addr.pedestrian || addr.residential || '';
          if (road && parts.indexOf(road) < 0) parts.push(road);

          var village = addr.village || addr.suburb || addr.quarter || addr.hamlet || '';
          if (village && parts.indexOf(village) < 0) parts.push(village);

          var district = addr.municipality || addr.town || addr.city_district || '';
          if (district && parts.indexOf(district) < 0) {
            parts.push(district.toLowerCase().indexOf('kec') >= 0 ? district : ('Kec. ' + district));
          }

          var county = addr.county || addr.city || addr.state_district || '';
          if (county && parts.indexOf(county) < 0) parts.push(county);

          var state = addr.state || '';
          if (state && parts.indexOf(state) < 0) parts.push(state);

          var postcode = addr.postcode || '';
          if (postcode && parts.indexOf(postcode) < 0) parts.push(postcode);

          var address = parts.length >= 2 ? parts.join(', ') : (data.display_name || ('Koordinat: ' + lat.toFixed(6) + ', ' + lng.toFixed(6)));
          applyAddressResult(lat, lng, address);
        })
        .catch(function () {
          var fallback = 'Koordinat: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
          applyAddressResult(lat, lng, fallback);
        });
    }

    function applyAddressResult(lat, lng, address) {
      lastValidLat = lat; lastValidLng = lng; currentLat = lat; currentLng = lng; hasSelectedLocation = true;
      if (latInput) latInput.value = lat.toFixed(7);
      if (lngInput) lngInput.value = lng.toFixed(7);
      if (mapAddressInput) mapAddressInput.value = address;
      if (marker) marker.setPopupContent('<b>📍 Lokasi Terpilih:</b><br>' + address).openPopup();
    }




    function revertToLastValidLocation() {
      currentLat = lastValidLat; currentLng = lastValidLng;
      if (marker) marker.setLatLng([lastValidLat, lastValidLng]);
      if (map) map.panTo([lastValidLat, lastValidLng]);
      if (latInput) latInput.value = lastValidLat.toFixed(7);
      if (lngInput) lngInput.value = lastValidLng.toFixed(7);
    }

    // ── STORE ORIGIN: Pelaminan Family Zainal ──
    var STORE_LAT = -2.9389551;
    var STORE_LNG = 104.8106462;

    function calculateHaversineKm(lat1, lon1, lat2, lon2) {
      var R = 6371;
      var dLat = (lat2 - lat1) * Math.PI / 180;
      var dLon = (lon2 - lon1) * Math.PI / 180;
      var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
      var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }

    function calculateShippingCost(distKm) {
      if (distKm <= 1.0) return 0; // GRATIS Area Dekat Gudang
      // Formulasi Calibrated Resmi GoBox Gojek Palembang:
      // Sampel 1: 3.9 km = Rp 134.000 | Sampel 2: 9.5 km = Rp 176.500
      // Base Fee Armada: Rp 105.000 | Tarif Normal: Rp 7.500/km
      var baseFee = 105000;
      var rawCost = 0;

      if (distKm <= 30.0) {
        rawCost = baseFee + (distKm * 7500);
      } else if (distKm <= 100.0) {
        var extraKm30 = distKm - 30.0;
        rawCost = baseFee + (30.0 * 7500) + (extraKm30 * 6000);
      } else {
        var extraKm100 = distKm - 100.0;
        rawCost = baseFee + (30.0 * 7500) + (70.0 * 6000) + (extraKm100 * 5000);
      }

      return Math.round(rawCost / 500) * 500;
    }

    function updateRealtimeShipping(lat, lng) {
      var selPickup = 'diantar';
      pickupRadios.forEach(function(r) { if (r.checked) selPickup = r.value; });

      var badgeEl = document.getElementById('shipping-distance-badge');
      var distEl = document.getElementById('distance-km-display');
      var costEl = document.getElementById('shipping-cost-display');
      var distInput = document.getElementById('delivery_distance_km');
      var costInput = document.getElementById('shipping_cost_input');
      var displayShippingCost = document.getElementById('displayShippingCost');
      var displaySubtotal = document.getElementById('displaySubtotal');
      var displayGrandTotal = document.getElementById('displayGrandTotal');
      var displayDpAmount = document.getElementById('displayDpAmount');
      var subtotalVal = displaySubtotal ? (parseFloat(displaySubtotal.getAttribute('data-value')) || 0) : 0;

      // ── Jika user belum memilih lokasi, tampilkan Rp 0 tanpa hitung jarak ──
      if (!hasSelectedLocation || selPickup === 'diambil') {
        if (badgeEl) badgeEl.style.display = 'none';
        if (distInput) distInput.value = '0';
        if (costInput) costInput.value = '0';
        if (displayShippingCost) displayShippingCost.textContent = 'Rp 0';
        var baseTotal = subtotalVal;
        if (displayGrandTotal) displayGrandTotal.textContent = 'Rp ' + baseTotal.toLocaleString('id-ID');
        if (displayDpAmount) displayDpAmount.textContent = 'Rp ' + (baseTotal * 0.5).toLocaleString('id-ID');
        return;
      }

      var distKm = calculateHaversineKm(STORE_LAT, STORE_LNG, lat, lng);
      var cost = calculateShippingCost(distKm);

      if (distEl) distEl.textContent = distKm.toFixed(1) + ' km';
      if (costEl) {
        if (cost === 0) {
          costEl.textContent = 'Rp 0 (GRATIS Area Dekat Workshop)';
          costEl.style.color = '#27ae60';
        } else {
          costEl.textContent = 'Rp ' + cost.toLocaleString('id-ID');
          costEl.style.color = 'var(--terracotta-dark)';
        }
      }

      if (badgeEl) badgeEl.style.display = 'block';
      if (distInput) distInput.value = distKm.toFixed(2);
      if (costInput) costInput.value = cost;

      if (displayShippingCost) {
        displayShippingCost.textContent = cost === 0 ? 'Rp 0' : ('Rp ' + cost.toLocaleString('id-ID'));
      }

      var grandTotal = subtotalVal + cost;
      var dpAmount = grandTotal * 0.5;
      if (displayGrandTotal) displayGrandTotal.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
      if (displayDpAmount) displayDpAmount.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
    }

    function updateLocation(lat, lng, fetchAddress) {
      if (!isInsideSouthSumatra(lat, lng)) { showCoverageWarning(lat.toFixed(4) + ', ' + lng.toFixed(4)); revertToLastValidLocation(); return; }
      currentLat = lat; currentLng = lng; hasSelectedLocation = true;
      if (latInput) latInput.value = lat.toFixed(7);
      if (lngInput) lngInput.value = lng.toFixed(7);
      if (marker) marker.setLatLng([lat, lng]);
      if (map) map.panTo([lat, lng]);
      updateRealtimeShipping(lat, lng);
      if (fetchAddress) reverseGeocode(lat, lng);
    }

    // ── AUTOCOMPLETE ──
    var searchInput = document.getElementById('map-search-input');
    var searchBtn = document.getElementById('btn-search-map');
    var autocompleteDropdown = document.getElementById('map-search-autocomplete');

    if (searchInput) {
      if (!autocompleteDropdown) {
        autocompleteDropdown = document.createElement('div');
        autocompleteDropdown.id = 'map-search-autocomplete';
        autocompleteDropdown.className = 'map-autocomplete-dropdown';
        var container = document.getElementById('map-search-wrapper') || (searchInput.parentNode ? searchInput.parentNode.parentNode : document.body);
        container.appendChild(autocompleteDropdown);
      }

      var debounceTimer = null, focusIdx = -1;

      function closeAC() { if (autocompleteDropdown) { autocompleteDropdown.style.display = 'none'; autocompleteDropdown.innerHTML = ''; } focusIdx = -1; }

      function renderList(list) {
        if (!autocompleteDropdown || !list || !list.length) { renderEmpty(); return; }
        autocompleteDropdown.innerHTML = '';
        list.forEach(function (item) {
          var div = document.createElement('div');
          div.className = 'autocomplete-item';
          div.innerHTML = '<div class="item-icon">📍</div><div class="item-text"><div class="item-title">' + esc(item.title) + '</div><div class="item-subtitle">' + esc(item.subtitle) + '</div></div>';
          div.addEventListener('mousedown', function (e) { e.preventDefault(); e.stopPropagation(); selectItem(item); });
          autocompleteDropdown.appendChild(div);
        });
        autocompleteDropdown.style.display = 'block';
      }

      function renderEmpty() {
        if (!autocompleteDropdown) return;
        autocompleteDropdown.innerHTML = '<div style="padding:14px;text-align:center;color:#888;font-size:13px;">📍 Tidak ditemukan. Coba nama jalan, kecamatan, atau kota di Sumatera Selatan.</div>';
        autocompleteDropdown.style.display = 'block';
      }

      function selectItem(item) {
        closeAC(); searchInput.value = item.title;
        updateClearBtnVisibility();
        var lat = parseFloat(item.lat), lng = parseFloat(item.lng);
        if (!isInsideSouthSumatra(lat, lng)) { showCoverageWarning(item.title); return; }
        updateLocation(lat, lng, true);
        if (map) { if (map.flyTo) map.flyTo([lat, lng], 15, { animate: true, duration: 1 }); else map.setView([lat, lng], 15); }
      }

      function fetchSuggestions(query) {
        var trimmed = (query || '').trim();
        if (trimmed.length < 2) { closeAC(); return; }
        var lq = trimmed.toLowerCase();

        var localMatches = [];
        LOCAL_LANDMARKS.forEach(function (lm) {
          var t = lm.title.toLowerCase();
          var s = lm.subtitle.toLowerCase();
          var score = 0;

          if (t === lq) score = 1000;
          else if (t.indexOf(lq) === 0) score = 800;
          else if (t.indexOf(lq) > 0) score = 500;
          else if (lm.keywords && lm.keywords.some(function (kw) { return kw.toLowerCase() === lq; })) score = 400;
          else if (lm.keywords && lm.keywords.some(function (kw) { return kw.toLowerCase().indexOf(lq) >= 0; })) score = 300;
          else if (s.indexOf(lq) >= 0) score = 100;

          if (score > 0) {
            localMatches.push({
              title: lm.title,
              subtitle: lm.subtitle,
              lat: lm.lat,
              lng: lm.lng,
              score: score
            });
          }
        });

        localMatches.sort(function (a, b) { return b.score - a.score; });

        var local = localMatches.map(function (item) {
          return { title: item.title, subtitle: item.subtitle, lat: item.lat, lng: item.lng };
        });

        if (local.length > 0) { renderList(local.slice(0, 9)); }
        else {
          if (autocompleteDropdown) {
            autocompleteDropdown.innerHTML = '<div style="padding:14px;text-align:center;color:#888;font-size:13px;display:flex;align-items:center;justify-content:center;gap:8px;"><span style="animation:spin 1s linear infinite;display:inline-block;">⏳</span> Mencari lokasi...</div>';
            autocompleteDropdown.style.display = 'block';
          }
        }

        var fullQ = (lq.indexOf('palembang') >= 0 || lq.indexOf('sumsel') >= 0 || lq.indexOf('sumatera') >= 0) ? trimmed : trimmed + ', Sumatera Selatan';
        var cacheKey = fullQ.toLowerCase();
        var cached = getCached(cacheKey);
        if (cached) { renderList(merge(local, cached)); return; }

        fetch('https://photon.komoot.io/api/?q=' + encodeURIComponent(fullQ) + '&lat=-2.9909&lon=104.7566&limit=6&lang=id')
          .then(function (r) { return r.json(); })
          .then(function (data) {
            var online = [];
            if (data && data.features) {
              data.features.forEach(function (f) {
                var p = f.properties || {};
                var coords = f.geometry && f.geometry.coordinates;
                if (!coords || coords.length < 2) return;
                var name = p.name || p.street || p.district || p.city || trimmed;
                var parts = [p.street !== name ? p.street : null, p.district, p.city, p.state].filter(Boolean);
                online.push({ title: name, subtitle: parts.join(', ') || 'Sumatera Selatan', lat: coords[1], lng: coords[0] });
              });
            }
            setCache(cacheKey, online);
            var merged = merge(local, online);
            if (merged.length > 0) renderList(merged); else arcGIS(local, cacheKey, trimmed);
          })
          .catch(function () { arcGIS(local, cacheKey, trimmed); });
      }

      function arcGIS(local, cacheKey, trimmed) {
        var q = trimmed + ', Palembang, Sumatera Selatan';
        fetch('https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates?f=json&singleLine=' + encodeURIComponent(q) + '&maxLocations=5&outFields=Match_addr')
          .then(function (r) { return r.json(); })
          .then(function (data) {
            var online = [];
            if (data && data.candidates) {
              data.candidates.forEach(function (c) {
                var parts = (c.address || '').split(',');
                online.push({ title: parts[0] || c.address, subtitle: parts.slice(1).join(',').trim() || 'Sumatera Selatan', lat: c.location.y, lng: c.location.x });
              });
            }
            setCache(cacheKey, online);
            var merged = merge(local, online);
            if (merged.length > 0) renderList(merged); else if (local.length > 0) renderList(local); else renderEmpty();
          })
          .catch(function () { if (local.length > 0) renderList(local); else renderEmpty(); });
      }

      function merge(local, online) {
        var res = local.slice();
        online.forEach(function (o) { if (!res.some(function (l) { return l.title.toLowerCase() === o.title.toLowerCase(); })) res.push(o); });
        return res.slice(0, 9);
      }

      function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

      function hlItem(items) {
        items.forEach(function (it, i) { it.classList.toggle('active', i === focusIdx); if (i === focusIdx) it.scrollIntoView({ block: 'nearest' }); });
      }

      // ── DYNAMIC ROTATING PLACEHOLDER (TYPEWRITER ANIMATION) ──
      var clearBtn = document.getElementById('btn-clear-map-search');
      var searchPhrases = [
        "Ketik nama jalan (misal: Jl. Jendral Sudirman Palembang)...",
        "Ketik perumahan (misal: Perumahan Kencana Indah Palembang)...",
        "Ketik toko / ruko (misal: Toko Nia Sako Rambutan)...",
        "Ketik patokan lokasi (misal: Dekat Jembatan Ampera)...",
        "Ketik nama gedung / mall (misal: OPI Mall Jakabaring)...",
        "Ketik nama masjid (misal: Masjid Baiturohim Sako)...",
        "Ketik nama kelurahan (misal: Sematang Borang, Palembang)..."
      ];

      var phraseIdx = 0;
      var charIdx = 0;
      var isDeleting = false;
      var typewriterTimer = null;
      var isSearchFocused = false;

      function updateClearBtnVisibility() {
        if (clearBtn) {
          clearBtn.style.display = (searchInput.value && searchInput.value.length > 0) ? 'flex' : 'none';
        }
      }

      if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          searchInput.value = '';
          updateClearBtnVisibility();
          closeAC();
          searchInput.focus();
        });
      }

      function typeLoop() {
        if (isSearchFocused || (searchInput.value && searchInput.value.length > 0)) {
          return;
        }

        var currentPhrase = searchPhrases[phraseIdx];
        var typingSpeed = isDeleting ? 25 : 48;

        if (!isDeleting) {
          charIdx++;
          searchInput.setAttribute('placeholder', currentPhrase.substring(0, charIdx));
          if (charIdx === currentPhrase.length) {
            isDeleting = true;
            typewriterTimer = setTimeout(typeLoop, 2200);
            return;
          }
        } else {
          charIdx--;
          searchInput.setAttribute('placeholder', currentPhrase.substring(0, charIdx));
          if (charIdx === 0) {
            isDeleting = false;
            phraseIdx = (phraseIdx + 1) % searchPhrases.length;
            typewriterTimer = setTimeout(typeLoop, 450);
            return;
          }
        }

        typewriterTimer = setTimeout(typeLoop, typingSpeed);
      }

      searchInput.addEventListener('focus', function () {
        isSearchFocused = true;
        clearTimeout(typewriterTimer);
        searchInput.setAttribute('placeholder', 'Ketik nama jalan, perumahan, toko, atau kelurahan di Sumsel...');
        if (searchInput.value.trim().length >= 2) fetchSuggestions(searchInput.value);
      });

      searchInput.addEventListener('blur', function () {
        isSearchFocused = false;
        if (!searchInput.value.trim()) {
          charIdx = 0;
          isDeleting = false;
          typewriterTimer = setTimeout(typeLoop, 600);
        }
      });

      searchInput.addEventListener('input', function () {
        updateClearBtnVisibility();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { fetchSuggestions(searchInput.value); }, 220);
      });

      // Start typewriter animation smoothly on load
      typewriterTimer = setTimeout(typeLoop, 900);

      searchInput.addEventListener('keydown', function (e) {
        var items = autocompleteDropdown ? autocompleteDropdown.querySelectorAll('.autocomplete-item') : [];
        if (e.key === 'ArrowDown') { e.preventDefault(); focusIdx = (focusIdx + 1) % Math.max(items.length, 1); hlItem(items); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); focusIdx = (focusIdx - 1 + Math.max(items.length, 1)) % Math.max(items.length, 1); hlItem(items); }
        else if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); if (focusIdx >= 0 && items[focusIdx]) items[focusIdx].dispatchEvent(new MouseEvent('mousedown')); else fetchSuggestions(searchInput.value); }
        else if (e.key === 'Escape') closeAC();
      });
      if (searchBtn) searchBtn.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); var q = searchInput.value.trim(); if (!q) { alert('Ketikkan nama lokasi di Sumatera Selatan.'); searchInput.focus(); return; } fetchSuggestions(q); });
      document.addEventListener('click', function (e) { if (searchInput && !searchInput.contains(e.target) && autocompleteDropdown && !autocompleteDropdown.contains(e.target)) closeAC(); });
    }

    // ── GEOLOCATION ──
    if (btnMyLocation) {
      btnMyLocation.addEventListener('click', function () {
        if (!navigator.geolocation) { alert('Geolocation tidak didukung browser ini.'); return; }
        btnMyLocation.disabled = true;
        var orig = btnMyLocation.innerHTML;
        btnMyLocation.innerHTML = '⌛ Mengambil Lokasi...';
        navigator.geolocation.getCurrentPosition(
          function (pos) {
            btnMyLocation.disabled = false; btnMyLocation.innerHTML = orig;
            var lat = pos.coords.latitude, lng = pos.coords.longitude;
            if (!isInsideSouthSumatra(lat, lng)) { showCoverageWarning('GPS Anda'); revertToLastValidLocation(); return; }
            updateLocation(lat, lng, true);
            if (map) { if (map.flyTo) map.flyTo([lat, lng], 17, { animate: true, duration: 1 }); else map.setView([lat, lng], 17); }
          },
          function (err) {
            btnMyLocation.disabled = false; btnMyLocation.innerHTML = orig;
            var msgs = { 1: 'Izin lokasi ditolak.', 2: 'Lokasi tidak tersedia.', 3: 'Waktu permintaan habis.' };
            alert(msgs[err.code] || 'Gagal mendapatkan lokasi.');
          },
          { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
      });
    }

    // ── MAP VISIBILITY ──
    function toggleMapVisibility() {
      var sel = 'diantar';
      pickupRadios.forEach(function (r) { if (r.checked) sel = r.value; });
      if (mapContainer) {
        mapContainer.style.display = sel === 'diantar' ? 'block' : 'none';
        if (sel === 'diantar' && map) setTimeout(function () { map.invalidateSize(); }, 200);
      }
      updateRealtimeShipping(currentLat, currentLng);
    }
    pickupRadios.forEach(function (r) { r.addEventListener('change', toggleMapVisibility); });
    toggleMapVisibility();

    if (hasSelectedLocation && mapAddressInput && !mapAddressInput.value) reverseGeocode(currentLat, currentLng);

    function refreshMap() { if (map) map.invalidateSize(); }
    [100, 400, 900].forEach(function (t) { setTimeout(refreshMap, t); });
    window.addEventListener('resize', refreshMap);
  }

})();
