<?php

namespace Database\Seeders;

use App\Models\Kandidat;
use App\Models\Kegiatan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KandidatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kandidat::factory()->createMany([
            [
                'id_kegiatan' => 1,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/BEM_1.png',
                'visi' => 'Mewujudkan harmoni antara kebijaksanaan dan kebajikan dalam setiap tindakan, guna membangun lingkungan yang tenang, beretika, dan penuh manfaat bagi sesama.',
                'misi' => '1. Membangun sistem komunikasi internal yang transparan, dua arah, dan efektif untuk menciptakan lingkungan kerja yang solid dan saling percaya. 2. Menciptakan program internalisasi yang memperkuat rasa kekeluargaan dan kepedulian antar anggota sebagai dasar komunikasi yang suportif. 3. Mengoptimalkan peran advokasi dengan menjadi penyalur aspirasi yang aktif dan responsif terhadap kebutuhan anggota. 4. Menjadikan media informasi organisasi sebagai pusat data dan berita yang akurat, cepat, dan mudah diakses untuk meningkatkan kesejahteraan akademik dan non-akademik anggota. 5. Menumbuhkan budaya organisasi yang inklusif, suportif, dan berlandaskan kekeluargaan sebagai fondasi utama dari keharmonisan internal',
            ],
            [
                'id_kegiatan' => 1,
                'no_urut' => '02',
                'visi' => 'Tidak ada visi yang diberikan.',
                'misi' => 'Tidak ada misi yang diberikan.',
            ],
            [
                'id_kegiatan' => 2,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMAKI_1.png',
                'visi' => 'Terwujudnya Himpunan Mahasiswa Kimia (Himaki) FMIPA Universitas Udayana sebagai organisasi yang solid, progresif, dan aspiratif, berlandaskan asas kekeluargaan demi Himaki yang maju dan terpandang.',
                'misi' => '1. Mempererat dan memupuk rasa kekeluargaan, kebersamaan, serta kepedulian di antara seluruh anggota Himaki FMIPA Unud. 2. Mewadahi dan memfasilitasi pengembangan potensi, minat, bakat, serta kreativitas mahasiswa Kimia di bidang akademik maupun non-akademik. 3. Menyelenggarakan program kerja yang inovatif, bermanfaat, dan berkelanjutan untuk meningkatkan kualitas sumber daya anggota dan menjawab aspirasi mahasiswa Kimia. 4. Menjalin dan menjaga hubungan yang harmonis serta sinergis dengan seluruh civitas akademika FMIPA Unud, lembaga kemahasiswaan lain, alumni, dan pihak eksternal. 5. Meningkatkan eksistensi dan citra positif Himaki FMIPA Unud di tingkat fakultas, universitas, dan masyarakat melalui kontribusi dan prestasi nyata.',
            ],
            [
                'id_kegiatan' => 2,
                'no_urut' => '02',
                'visi' => 'Tidak ada visi yang diberikan.',
                'misi' => 'Tidak ada misi yang diberikan.',
            ],
            [
                'id_kegiatan' => 3,
                'no_urut' => '01',
                'visi' => 'Tidak ada visi yang diberikan.',
                'misi' => 'Tidak ada misi yang diberikan.',
            ],
            [
                'id_kegiatan' => 3,
                'no_urut' => '02',
                'foto' => 'foto-kandidat/HIMAFI_2.png',
                'visi' => 'Terwujudnya Himpunan Mahasiswa Fisika sebagai pusat inovasi dan wadah pengembangan potensi mahasiswa sesuai dengan bakat dan minat.',
                'misi' => '1. Mewadahi pengembangan potensi mahasiswa melalui komunitas dan pembinaan secara aktif dan rutin. 2. Membangun iklim organisasi yang terbuka, inklusif, dan mendukung setiap anggota untuk bertumbuh dan berprestasi dalam bidang akademik maupun non akademik. 3. Mengoptimalkan pemanfaatan teknologi digital dalam tata kelola organisasi dan pelayanan anggota untuk mencapai efisiensi dan transparansi. 4. Menjadi agen perubahan dalam pengembangan sains dan teknologi sehingga dapat bermanfaat secara nyata di lingkungan masyarakat.',
            ],
            [
                'id_kegiatan' => 4,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMABIO_1.png',
                'visi' => 'Mengolah semangat HIMABIO yang Berintegritas dan Aktif dengan Mewujudkan Lingkungan Jujur dan Terbuka dalam Penataan Kualitas Individu.',
                'misi' => '1. Meningkatkan kinerja Himpunan Mahasiswa Biologi yang inspiratif, kolaboratif, dan berorientasi pada produktivitas. 2. Mendorong keaktifan dan mempererat rasa kekeluargaan antaranggota melalui partisipasi aktif dalam berbagai kegiatan dan inisiatif nyata. 3. Mengembangkan potensi mahasiswa Biologi sebagai wujud kontribusi terhadap pelaksanaan Tri Dharma Perguruan Tinggi. 4. Melaksanakan program kerja yang berfokus pada pengembangan kreativitas, keterampilan, serta semangat aktif seluruh anggota.',
            ],
            [
                'id_kegiatan' => 4,
                'no_urut' => '02',
                'visi' => 'Tidak ada visi yang diberikan.',
                'misi' => 'Tidak ada misi yang diberikan.',
            ],
            [
                'id_kegiatan' => 5,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMATIKA_1.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa yang proaktif dalam pengembangan diri dan organisasi, guna membangun keselarasan civitas akademika program studi matematika yang positif, suportif, dan inklusif.',
                'misi' => '1. Mengorganisir berbagai kegiatan yang memfasilitasi pengembangan keterampilan, pengetahuan, dan kepemimpinan mahasiswa matematika. 2. Memberikan kesempatan kepada mahasiswa matematika untuk mengambil peran kepemimpinan dalam berbagai kegiatan, guna membangun rasa tanggung jawab dan keterampilan manajemen. 3. Mengimplementasikan sistem partisipasi aktif dalam pengambilan keputusan dan pengelolaan organisasi. 4. Menyediakan ruang bagi mahasiswa matematika untuk mengekspresikan ide-ide dan gagasan-gagasan baru guna menampung aspirasi mahasiswa program studi Matematika. 5. Menyelaraskan seluruh kegiatan dengan visi utama guna mewujudkan cita-cita sebagai organisasi yang mampu mendukung perkembangan bersama dan mengakomodasi aspirasi mahasiswa matematika secara efektif.',
            ],
            [
                'id_kegiatan' => 5,
                'no_urut' => '02',
                'foto' => 'foto-kandidat/HIMATIKA_2.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Matematika Universitas Udayana yang solid, berdaya guna, dan berperan aktif dalam menciptakan lingkungan akademik yang harmonis, kolaboratif, serta berdampak positif bagi civitas akademika dan masyarakat.',
                'misi' => '1. Menumbuhkan rasa kebersamaan dan solidaritas di antara seluruh anggota dan fungsionaris HIMATIKA melalui kegiatan internal yang inklusif, komunikatif, dan berkelanjutan. 2. Mengoptimalkan kinerja dan koordinasi antarbidang agar setiap program kerja berjalan efektif, efisien, dan berdampak nyata, yang dapat . Meningkatkan citra HIMATIKA UNUD sebagai organisasi yang aktif, profesional, dan berintegritas melalui kegiatan akademik, sosial, dan kolaboratif. 3. Mendorong partisipasi aktif mahasiswa matematika dalam kegiatan ilmiah, sosial, dan pengembangan diri di tingkat fakultas maupun universitas. 4. Menjalin sinergi dan kolaborasi dengan himpunan lain, lembaga kampus, serta pihak eksternal untuk memperluas jejaring dan memperkuat eksistensi HIMATIKA UNUD.',
            ],
            [
                'id_kegiatan' => 5,
                'no_urut' => '03',
                'foto' => 'foto-kandidat/HIMATIKA_3.png',
                'visi' => 'Mewujudkan HIMATIKA sebagai wadah yang menyatukan seluruh mahasiswa Matematika Universitas Udayana dalam semangat kebersamaan, kolaborasi, serta pengembangan potensi lintas angkatan untuk menciptakan lingkungan yang aktif, harmonis, dan inspiratif.',
                'misi' => '1. Mendorong mahasiswa untuk berpartisipasi aktif dalam setiap kegiatan HIMATIKA dengan semangat kekeluargaan dan rasa memiliki terhadap organisasi. 2. Menumbuhkan rasa kebersamaan dan solidaritas lintas angkatan melalui kegiatan yang mempererat hubungan antar mahasiswa Matematika. 3. Mewadahi pengembangan kemampuan akademik, soft skill, dan minat bakat mahasiswa melalui kegiatan yang edukatif, kreatif, dan berkelanjutan. 4. Mengoptimalkan peran HIMATIKA sebagai penghubung dan ruang kolaborasi antara mahasiswa, pengurus, dan alumni guna membangun sinergi yang positif bagi seluruh civitas mahasiswa Matematika.',
            ],
            [
                'id_kegiatan' => 6,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMAFARMA_1.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Farmasi Universitas Udayana sebagai rumah yang harmonis, solid, dan progresif; tempat seluruh mahasiswa tumbuh bersama dalam semangat kekeluargaan, kolaborasi, dan pengabdian.',
                'misi' => '1. Menumbuhkan rasa kekeluargaan dan solidaritas di seluruh lapisan mahasiswa Farmasi Udayana melalui kegiatan yang membangun kebersamaan, saling dukung, dan kepedulian antaranggota. 2. Membangun komunikasi yang terbuka dan kolaboratif antara mahasiswa, pengurus, dan dosen agar tercipta lingkungan akademik yang harmonis, inklusif, dan berorientasi pada solusi bersama. 3. Mendorong pengembangan potensi dan karakter mahasiswa dalam suasana yang hangat dan suportif, sehingga setiap individu dapat tumbuh menjadi pribadi yang berkompeten, berempati, dan siap berkontribusi bagi almamater.',
            ],
            [
                'id_kegiatan' => 6,
                'no_urut' => '02',
                'foto' => 'foto-kandidat/HIMAFARMA_2.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Farmasi Udayana sebagai organisasi yang progresif, inklusif, dan inovatif dengan menumbuhkan semangat kekeluargaan, kolaborasi, serta sinergi yang berkelanjutan baik di lingkungan internal maupun eksternal organisasi.',
                'misi' => '1. Penguatan Internal dan Kekeluargaan - Membangun suasana organisasi yang solid dan harmonis melalui komunikasi terbuka, kerja sama yang erat, serta semangat kekeluargaan di antara seluruh anggota. 2. Inovasi dan Pengembangan Potensi Mahasiswa Farmasi - Mengoptimalkan Himpunan Mahasiswa Farmasi Udayana sebagai wadah pengembangan ide, kreativitas, dan profesionalitas mahasiswa melalui program kerja yang relevan, edukatif, dan berdampak nyata. 3. Sinergi dan Kolaborasi Eksternal - Menjalin kerja sama yang konstruktif dan berkelanjutan dengan himpunan mahasiswa lain, lembaga kemahasiswaan, serta organisasi eksternal guna memperluas jaringan dan meningkatkan kontribusi Himpunan Mahasiswa Farmasi Udayana di tingkat universitas maupun nasional.',
            ],
            [
                'id_kegiatan' => 6,
                'no_urut' => '03',
                'foto' => 'foto-kandidat/HIMAFARMA_3.png',
                'visi' => 'Menjadikan Himpunan Mahasiswa Farmasi Udayana yang unggul, berintegritas, dan berdaya saing dalam ilmu kefarmasian serta berperan aktif dalam pengabdian kepada masyarakat berlandaskan Tri Dharma Perguruan Tinggi.',
                'misi' => '1. Meningkatkan Kualitas dan Kompetensi Mahasiswa Farmasi - Mahasiswa Farmasi Udayana diharapkan memiliki kemampuan akademik dan profesional yang mumpuni agar mampu bersaing di dunia kerja maupun akademik. Peningkatan kompetensi akan dilakukan melalui program kerja himafarma yang bersangkutan langsung dengan bidang akademik serta peningkatan soft skill mahasiswa farmasi. 2. Mendorong Budaya Riset dan Inovasi Mahasiswa Farmasi - Sebagai bagian dari Tri Dharma Perguruan Tinggi, riset dan inovasi menjadi pilar penting dalam pengembangan ilmu pengetahuan. Himafarma dapat menjadi fasilitator bagi mahasiswa untuk aktif dalam penelitian dan pengembangan inovasi di bidang farmasi melalui penyediaan media publikasi dan memfasilitasi pengajuan proposal PKM. 3. Meningkatkan Kontribusi Sosial dan Pengabdian kepada Masyarakat - Sebagai calon tenaga kesehatan, mahasiswa farmasi memiliki tanggung jawab moral untuk memberikan manfaat nyata bagi masyarakat. Himafarma akan menjalankan program kerja yang langsung berhubungan dengan masyarakat dengan memberikan edukasi, sosialisasi, atau bantuan langsung sebagai bentuk pengabdian mahasiswa farmasi kepada masyarakat.',
            ],
            [
                'id_kegiatan' => 7,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMAIF_1.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Informatika Udayana yang dinamis, solutif, dan proaktif dalam menjembatani aspirasi serta mendukung pengembangan kegiatan kemahasiswaan dengan berlandaskan profesionalisme dan kekeluargaan.',
                'misi' => '1. Menjalankan sistem kerja yang berkelanjutan dengan mengoptimalkan program yang telah ada, serta adaptif terhadap dinamika kampus dan kebutuhan mahasiswa. 2. Menumbuhkan sikap proaktif dan inisiatif dalam setiap lini kepengurusan melalui pengembangan dan inovasi berkelanjutan pada program kerja. 3. Membangun budaya komunikasi yang solutif dan responsif melalui mekanisme penyampaian aspirasi yang terbuka dan terstruktur di tingkat internal maupun eksternal organisasi. 4. Memfasilitasi pengembangan kegiatan kemahasiswaan yang berorientasi pada peningkatan soft skill, pengembangan prestasi mahasiswa, dan solidaritas antar angkatan. 5. Menegakkan profesionalisme berlandaskan kekeluargaan dengan menumbuhkan rasa tanggung jawab, rasa memiliki, dan suasana kerja yang harmonis serta inklusif dalam organisasi.',
            ],
            [
                'id_kegiatan' => 7,
                'no_urut' => '02',
                'foto' => 'foto-kandidat/HIMAIF_2.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Informatika Udayana sebagai wadah aspirasi yang aktif, adaptif, dan inklusif dengan menjunjung tinggi nilai kekeluargaan, solidaritas, serta profesionalisme dalam setiap aspek kegiatan kemahasiswaan.',
                'misi' => '1. Menjadi wadah bagi mahasiswa dalam mengembangkan minat, bakat, dan potensi di bidang akademik maupun non-akademik. 2. Meningkatkan komunikasi dan koordinasi, baik internal maupun eksternal, guna menyelaraskan kegiatan kemahasiswaan dan kegiatan akademik secara sinergis. 3. Mempererat hubungan kekeluargaan dan kolaborasi di antara seluruh elemen civitas akademika Program Studi Informatika Udayana, untuk menciptakan lingkungan yang harmonis, inklusif, dan saling mendukung. 4. Menumbuhkan etos kerja yang profesional, bertanggung jawab, dan adaptif sebagai landasan terciptanya Himpunan Mahasiswa Informatika Udayana yang berintegritas.',
            ],
            [
                'id_kegiatan' => 8,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/BEM_1.png',
                'visi' => 'Mewujudkan harmoni antara kebijaksanaan dan kebajikan dalam setiap tindakan, guna membangun lingkungan yang tenang, beretika, dan penuh manfaat bagi sesama.',
                'misi' => '1. Membangun sistem komunikasi internal yang transparan, dua arah, dan efektif untuk menciptakan lingkungan kerja yang solid dan saling percaya. 2. Menciptakan program internalisasi yang memperkuat rasa kekeluargaan dan kepedulian antar anggota sebagai dasar komunikasi yang suportif. 3. Mengoptimalkan peran advokasi dengan menjadi penyalur aspirasi yang aktif dan responsif terhadap kebutuhan anggota. 4. Menjadikan media informasi organisasi sebagai pusat data dan berita yang akurat, cepat, dan mudah diakses untuk meningkatkan kesejahteraan akademik dan non-akademik anggota. 5. Menumbuhkan budaya organisasi yang inklusif, suportif, dan berlandaskan kekeluargaan sebagai fondasi utama dari keharmonisan internal',
            ],
            [
                'id_kegiatan' => 8,
                'no_urut' => '02',
                'visi' => 'Tidak ada visi yang diberikan.',
                'misi' => 'Tidak ada misi yang diberikan.',
            ],
            [
                'id_kegiatan' => 9,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMAKI_1.png',
                'visi' => 'Terwujudnya Himpunan Mahasiswa Kimia (Himaki) FMIPA Universitas Udayana sebagai organisasi yang solid, progresif, dan aspiratif, berlandaskan asas kekeluargaan demi Himaki yang maju dan terpandang.',
                'misi' => '1. Mempererat dan memupuk rasa kekeluargaan, kebersamaan, serta kepedulian di antara seluruh anggota Himaki FMIPA Unud. 2. Mewadahi dan memfasilitasi pengembangan potensi, minat, bakat, serta kreativitas mahasiswa Kimia di bidang akademik maupun non-akademik. 3. Menyelenggarakan program kerja yang inovatif, bermanfaat, dan berkelanjutan untuk meningkatkan kualitas sumber daya anggota dan menjawab aspirasi mahasiswa Kimia. 4. Menjalin dan menjaga hubungan yang harmonis serta sinergis dengan seluruh civitas akademika FMIPA Unud, lembaga kemahasiswaan lain, alumni, dan pihak eksternal. 5. Meningkatkan eksistensi dan citra positif Himaki FMIPA Unud di tingkat fakultas, universitas, dan masyarakat melalui kontribusi dan prestasi nyata.',
            ],
            [
                'id_kegiatan' => 9,
                'no_urut' => '02',
                'visi' => 'Tidak ada visi yang diberikan.',
                'misi' => 'Tidak ada misi yang diberikan.',
            ],
            [
                'id_kegiatan' => 10,
                'no_urut' => '01',
                'visi' => 'Tidak ada visi yang diberikan.',
                'misi' => 'Tidak ada misi yang diberikan.',
            ],
            [
                'id_kegiatan' => 10,
                'no_urut' => '02',
                'foto' => 'foto-kandidat/HIMAFI_2.png',
                'visi' => 'Terwujudnya Himpunan Mahasiswa Fisika sebagai pusat inovasi dan wadah pengembangan potensi mahasiswa sesuai dengan bakat dan minat.',
                'misi' => '1. Mewadahi pengembangan potensi mahasiswa melalui komunitas dan pembinaan secara aktif dan rutin. 2. Membangun iklim organisasi yang terbuka, inklusif, dan mendukung setiap anggota untuk bertumbuh dan berprestasi dalam bidang akademik maupun non akademik. 3. Mengoptimalkan pemanfaatan teknologi digital dalam tata kelola organisasi dan pelayanan anggota untuk mencapai efisiensi dan transparansi. 4. Menjadi agen perubahan dalam pengembangan sains dan teknologi sehingga dapat bermanfaat secara nyata di lingkungan masyarakat.',
            ],
            [
                'id_kegiatan' => 11,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMABIO_1.png',
                'visi' => 'Mengolah semangat HIMABIO yang Berintegritas dan Aktif dengan Mewujudkan Lingkungan Jujur dan Terbuka dalam Penataan Kualitas Individu.',
                'misi' => '1. Meningkatkan kinerja Himpunan Mahasiswa Biologi yang inspiratif, kolaboratif, dan berorientasi pada produktivitas. 2. Mendorong keaktifan dan mempererat rasa kekeluargaan antaranggota melalui partisipasi aktif dalam berbagai kegiatan dan inisiatif nyata. 3. Mengembangkan potensi mahasiswa Biologi sebagai wujud kontribusi terhadap pelaksanaan Tri Dharma Perguruan Tinggi. 4. Melaksanakan program kerja yang berfokus pada pengembangan kreativitas, keterampilan, serta semangat aktif seluruh anggota.',
            ],
            [
                'id_kegiatan' => 11,
                'no_urut' => '02',
                'visi' => 'Tidak ada visi yang diberikan.',
                'misi' => 'Tidak ada misi yang diberikan.',
            ],
            [
                'id_kegiatan' => 12,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMATIKA_1.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa yang proaktif dalam pengembangan diri dan organisasi, guna membangun keselarasan civitas akademika program studi matematika yang positif, suportif, dan inklusif.',
                'misi' => '1. Mengorganisir berbagai kegiatan yang memfasilitasi pengembangan keterampilan, pengetahuan, dan kepemimpinan mahasiswa matematika. 2. Memberikan kesempatan kepada mahasiswa matematika untuk mengambil peran kepemimpinan dalam berbagai kegiatan, guna membangun rasa tanggung jawab dan keterampilan manajemen. 3. Mengimplementasikan sistem partisipasi aktif dalam pengambilan keputusan dan pengelolaan organisasi. 4. Menyediakan ruang bagi mahasiswa matematika untuk mengekspresikan ide-ide dan gagasan-gagasan baru guna menampung aspirasi mahasiswa program studi Matematika. 5. Menyelaraskan seluruh kegiatan dengan visi utama guna mewujudkan cita-cita sebagai organisasi yang mampu mendukung perkembangan bersama dan mengakomodasi aspirasi mahasiswa matematika secara efektif.',
            ],
            [
                'id_kegiatan' => 12,
                'no_urut' => '02',
                'foto' => 'foto-kandidat/HIMATIKA_2.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Matematika Universitas Udayana yang solid, berdaya guna, dan berperan aktif dalam menciptakan lingkungan akademik yang harmonis, kolaboratif, serta berdampak positif bagi civitas akademika dan masyarakat.',
                'misi' => '1. Menumbuhkan rasa kebersamaan dan solidaritas di antara seluruh anggota dan fungsionaris HIMATIKA melalui kegiatan internal yang inklusif, komunikatif, dan berkelanjutan. 2. Mengoptimalkan kinerja dan koordinasi antarbidang agar setiap program kerja berjalan efektif, efisien, dan berdampak nyata, yang dapat . Meningkatkan citra HIMATIKA UNUD sebagai organisasi yang aktif, profesional, dan berintegritas melalui kegiatan akademik, sosial, dan kolaboratif. 3. Mendorong partisipasi aktif mahasiswa matematika dalam kegiatan ilmiah, sosial, dan pengembangan diri di tingkat fakultas maupun universitas. 4. Menjalin sinergi dan kolaborasi dengan himpunan lain, lembaga kampus, serta pihak eksternal untuk memperluas jejaring dan memperkuat eksistensi HIMATIKA UNUD.',
            ],
            [
                'id_kegiatan' => 12,
                'no_urut' => '03',
                'foto' => 'foto-kandidat/HIMATIKA_3.png',
                'visi' => 'Mewujudkan HIMATIKA sebagai wadah yang menyatukan seluruh mahasiswa Matematika Universitas Udayana dalam semangat kebersamaan, kolaborasi, serta pengembangan potensi lintas angkatan untuk menciptakan lingkungan yang aktif, harmonis, dan inspiratif.',
                'misi' => '1. Mendorong mahasiswa untuk berpartisipasi aktif dalam setiap kegiatan HIMATIKA dengan semangat kekeluargaan dan rasa memiliki terhadap organisasi. 2. Menumbuhkan rasa kebersamaan dan solidaritas lintas angkatan melalui kegiatan yang mempererat hubungan antar mahasiswa Matematika. 3. Mewadahi pengembangan kemampuan akademik, soft skill, dan minat bakat mahasiswa melalui kegiatan yang edukatif, kreatif, dan berkelanjutan. 4. Mengoptimalkan peran HIMATIKA sebagai penghubung dan ruang kolaborasi antara mahasiswa, pengurus, dan alumni guna membangun sinergi yang positif bagi seluruh civitas mahasiswa Matematika.',
            ],
            [
                'id_kegiatan' => 13,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMAFARMA_1.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Farmasi Universitas Udayana sebagai rumah yang harmonis, solid, dan progresif; tempat seluruh mahasiswa tumbuh bersama dalam semangat kekeluargaan, kolaborasi, dan pengabdian.',
                'misi' => '1. Menumbuhkan rasa kekeluargaan dan solidaritas di seluruh lapisan mahasiswa Farmasi Udayana melalui kegiatan yang membangun kebersamaan, saling dukung, dan kepedulian antaranggota. 2. Membangun komunikasi yang terbuka dan kolaboratif antara mahasiswa, pengurus, dan dosen agar tercipta lingkungan akademik yang harmonis, inklusif, dan berorientasi pada solusi bersama. 3. Mendorong pengembangan potensi dan karakter mahasiswa dalam suasana yang hangat dan suportif, sehingga setiap individu dapat tumbuh menjadi pribadi yang berkompeten, berempati, dan siap berkontribusi bagi almamater.',
            ],
            [
                'id_kegiatan' => 13,
                'no_urut' => '02',
                'foto' => 'foto-kandidat/HIMAFARMA_2.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Farmasi Udayana sebagai organisasi yang progresif, inklusif, dan inovatif dengan menumbuhkan semangat kekeluargaan, kolaborasi, serta sinergi yang berkelanjutan baik di lingkungan internal maupun eksternal organisasi.',
                'misi' => '1. Penguatan Internal dan Kekeluargaan - Membangun suasana organisasi yang solid dan harmonis melalui komunikasi terbuka, kerja sama yang erat, serta semangat kekeluargaan di antara seluruh anggota. 2. Inovasi dan Pengembangan Potensi Mahasiswa Farmasi - Mengoptimalkan Himpunan Mahasiswa Farmasi Udayana sebagai wadah pengembangan ide, kreativitas, dan profesionalitas mahasiswa melalui program kerja yang relevan, edukatif, dan berdampak nyata. 3. Sinergi dan Kolaborasi Eksternal - Menjalin kerja sama yang konstruktif dan berkelanjutan dengan himpunan mahasiswa lain, lembaga kemahasiswaan, serta organisasi eksternal guna memperluas jaringan dan meningkatkan kontribusi Himpunan Mahasiswa Farmasi Udayana di tingkat universitas maupun nasional.',
            ],
            [
                'id_kegiatan' => 13,
                'no_urut' => '03',
                'foto' => 'foto-kandidat/HIMAFARMA_3.png',
                'visi' => 'Menjadikan Himpunan Mahasiswa Farmasi Udayana yang unggul, berintegritas, dan berdaya saing dalam ilmu kefarmasian serta berperan aktif dalam pengabdian kepada masyarakat berlandaskan Tri Dharma Perguruan Tinggi.',
                'misi' => '1. Meningkatkan Kualitas dan Kompetensi Mahasiswa Farmasi - Mahasiswa Farmasi Udayana diharapkan memiliki kemampuan akademik dan profesional yang mumpuni agar mampu bersaing di dunia kerja maupun akademik. Peningkatan kompetensi akan dilakukan melalui program kerja himafarma yang bersangkutan langsung dengan bidang akademik serta peningkatan soft skill mahasiswa farmasi. 2. Mendorong Budaya Riset dan Inovasi Mahasiswa Farmasi - Sebagai bagian dari Tri Dharma Perguruan Tinggi, riset dan inovasi menjadi pilar penting dalam pengembangan ilmu pengetahuan. Himafarma dapat menjadi fasilitator bagi mahasiswa untuk aktif dalam penelitian dan pengembangan inovasi di bidang farmasi melalui penyediaan media publikasi dan memfasilitasi pengajuan proposal PKM. 3. Meningkatkan Kontribusi Sosial dan Pengabdian kepada Masyarakat - Sebagai calon tenaga kesehatan, mahasiswa farmasi memiliki tanggung jawab moral untuk memberikan manfaat nyata bagi masyarakat. Himafarma akan menjalankan program kerja yang langsung berhubungan dengan masyarakat dengan memberikan edukasi, sosialisasi, atau bantuan langsung sebagai bentuk pengabdian mahasiswa farmasi kepada masyarakat.',
            ],
            [
                'id_kegiatan' => 14,
                'no_urut' => '01',
                'foto' => 'foto-kandidat/HIMAIF_1.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Informatika Udayana yang dinamis, solutif, dan proaktif dalam menjembatani aspirasi serta mendukung pengembangan kegiatan kemahasiswaan dengan berlandaskan profesionalisme dan kekeluargaan.',
                'misi' => '1. Menjalankan sistem kerja yang berkelanjutan dengan mengoptimalkan program yang telah ada, serta adaptif terhadap dinamika kampus dan kebutuhan mahasiswa. 2. Menumbuhkan sikap proaktif dan inisiatif dalam setiap lini kepengurusan melalui pengembangan dan inovasi berkelanjutan pada program kerja. 3. Membangun budaya komunikasi yang solutif dan responsif melalui mekanisme penyampaian aspirasi yang terbuka dan terstruktur di tingkat internal maupun eksternal organisasi. 4. Memfasilitasi pengembangan kegiatan kemahasiswaan yang berorientasi pada peningkatan soft skill, pengembangan prestasi mahasiswa, dan solidaritas antar angkatan. 5. Menegakkan profesionalisme berlandaskan kekeluargaan dengan menumbuhkan rasa tanggung jawab, rasa memiliki, dan suasana kerja yang harmonis serta inklusif dalam organisasi.',
            ],
            [
                'id_kegiatan' => 14,
                'no_urut' => '02',
                'foto' => 'foto-kandidat/HIMAIF_2.png',
                'visi' => 'Mewujudkan Himpunan Mahasiswa Informatika Udayana sebagai wadah aspirasi yang aktif, adaptif, dan inklusif dengan menjunjung tinggi nilai kekeluargaan, solidaritas, serta profesionalisme dalam setiap aspek kegiatan kemahasiswaan.',
                'misi' => '1. Menjadi wadah bagi mahasiswa dalam mengembangkan minat, bakat, dan potensi di bidang akademik maupun non-akademik. 2. Meningkatkan komunikasi dan koordinasi, baik internal maupun eksternal, guna menyelaraskan kegiatan kemahasiswaan dan kegiatan akademik secara sinergis. 3. Mempererat hubungan kekeluargaan dan kolaborasi di antara seluruh elemen civitas akademika Program Studi Informatika Udayana, untuk menciptakan lingkungan yang harmonis, inklusif, dan saling mendukung. 4. Menumbuhkan etos kerja yang profesional, bertanggung jawab, dan adaptif sebagai landasan terciptanya Himpunan Mahasiswa Informatika Udayana yang berintegritas.',
            ],
        ]);

        // NIM Kandidat
        $nimBEMFMIPA = [['2308521032', '2308561057'], ['0000000001', '0000000002']];
        $nimHIMA = [
            1 => ['2308511014', '0000000051'],
            2 => ['0000000052', '2308521009'],
            3 => ['2308531027', '0000000053'],
            4 => ['2308541003', '2408541053', '2408541001'],
            5 => ['2308551040', '2308551093', '2308551088'],
            6 => ['2308561089', '2308561029'],
        ];

        // Proses setiap kandidat yang telah dibuat
        for ($i = 1; $i <= 14; $i++) {
            $ruangLingkup = $i % 7 == 1 ? 'fakultas' : 'program studi';
            $kandidats = Kandidat::where('id_kegiatan', $i)->orderBy('no_urut')->get();

            if ($ruangLingkup === 'fakultas') {
                foreach ($kandidats as $index => $kandidat) {
                    if (isset($nimBEMFMIPA[$index])) {
                        $kandidat->mahasiswa()->attach($nimBEMFMIPA[$index][0], [
                            'jabatan' => 'ketua',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $kandidat->mahasiswa()->attach($nimBEMFMIPA[$index][1], [
                            'jabatan' => 'wakil',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } else if ($ruangLingkup === 'program studi') {
                $idProgramStudi = Kegiatan::find($i)->id_program_studi;
                if (isset($nimHIMA[$idProgramStudi])) {
                    foreach ($kandidats as $index => $kandidat) {
                        if (isset($nimHIMA[$idProgramStudi][$index])) {
                            $kandidat->mahasiswa()->attach($nimHIMA[$idProgramStudi][$index], [
                                'jabatan' => 'ketua',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }
}
