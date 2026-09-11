# Analisis teknis audio referensi — belum merupakan hasil rekonstruksi

Sumber: `Opening - Ekspedisi Wonderland Indonesia.mp3`

## Hasil pengukuran referensi

- Format sumber: MP3 320 kbps, 44,1 kHz, stereo.
- Durasi audio terdekode: 60.709 detik.
- Integrated loudness: -13.93 LUFS.
- True peak: -2.62 dBTP (pengukuran FFmpeg loudnorm).
- Loudness range: 17.80 LU.
- Sample peak terdekode: -2.65 dBFS.
- Jumlah sampel terdekode pada/di atas full scale: 0.
- Korelasi kanal stereo keseluruhan: 0.476; angka ini bukan jaminan bebas masalah fase pada setiap frekuensi.
- MP3 memakai kompresi lossy; bit depth PCM 24-bit tidak berlaku sebagai spesifikasi intrinsik file MP3.

Metadata stream asli tersedia di reference-measurements.json. Dekode 48 kHz stereo hanya dipakai dalam memori untuk analisis; tidak diekspor sebagai master baru.

## Tempo dan struktur

Target brief: sekitar 117 BPM. Kandidat estimasi otomatis berbasis autocorrelation spectral flux: 117.19 BPM, 175.78 BPM, 87.89 BPM, 70.31 BPM, 140.62 BPM. Estimasi dapat memiliki ambiguitas half/double-time dan belum dikonfirmasi lewat pendengaran atau beat grid.

Batas bagian berikut berasal dari brief pengguna, bukan hasil deteksi struktur musik yang telah dikonfirmasi. RMS merupakan rata-rata amplitudo bagian, bukan LUFS per bagian.

| Bagian dari brief | Waktu (detik) | RMS dBFS | Sample peak dBFS |
|---|---:|---:|---:|
| Intro | 0.00–12.00 | -28.40 | -6.20 |
| Early build | 12.00–24.00 | -16.18 | -3.80 |
| Main development | 24.00–42.00 | -15.68 | -2.65 |
| Climax | 42.00–56.00 | -15.54 | -4.81 |
| Ending | 56.00–60.71 | -39.81 | -21.06 |

## Status produksi dan batas analisis

Belum ada komposisi baru, master WAV, master MP3, atau preview perbandingan. Sesi ini tidak memiliki alat generasi musik/audio-to-audio yang terhubung; penelusuran alat juga tidak menemukan akses tersebut. Rekonstruksi dengan instrumen orkestra realistis dan evaluasi pendengaran penuh belum dapat dilakukan dengan kemampuan yang tersedia. Mengonversi, melakukan EQ, atau menaikkan volume file sumber tidak memenuhi brief sehingga tidak dilakukan.

Identifikasi melodi, akor, tonalitas, dan instrumen aktual belum diverifikasi. Tidak ada klaim bahwa audio telah didengarkan atau bahwa seluruh noise, artefak, klik, dan transisi sudah lolos pemeriksaan. Sampel di bawah full scale tidak membuktikan bahwa rekaman tidak pernah mengalami clipping sebelum pengodean MP3.

## Spesifikasi produksi berikutnya

Gunakan file MP3 asli sebagai referensi pada mesin generasi musik yang menerima audio, atau lakukan aransemen ulang dalam DAW memakai pustaka orkestra berkualitas. Pertahankan durasi 60–62 detik, sekitar 117 BPM, tanpa vokal, dengan intro lapang, build bertahap, tema utama emosional, klimaks pada 42–56 detik, dan resolusi berdecay alami pada 56–61 detik. Piano, violin, viola, cello, double bass, French horn/brass hangat, perkusi orkestra lembut, serta pad instrumental adalah instrumen yang diminta brief; bukan hasil identifikasi terverifikasi dari sumber.

Setelah komposisi baru tersedia: evaluasi seluruh audio, perbaiki aransemen/mix, master ke -14 hingga -12 LUFS dan true peak maksimal -1 dBTP, ekspor WAV PCM 24-bit/48 kHz stereo dan MP3 320 kbps/48 kHz stereo, lalu ukur ulang kedua ekspor. Buat preview A/B dari segmen yang sepadan dengan level yang disamakan agar perbandingan kejernihan tidak bias oleh volume.

Brief lengkap tersimpan dalam production-brief.txt. Pekerjaan masih belum selesai; laporan ini hanya menyelesaikan pengukuran teknis awal referensi.

