# anket-plus-script.
anket scripti, araştırmacılar için oluşturuldu. anketler sadece bağlantıya sahip kişiler tarafından görülür mysql desteklidir. anket sonuçlarını, sadece, yöneticiler görür, katılımcılar göremezler. anketler raporlanabilir, .csv formatında raporları alabilirsiniz.

## Kurulum
1. `config.php` içindeki MySQL bilgilerini düzenleyin (veritabanını önceden oluşturun).
2. `install.php` sayfasını açıp yönetici hesabını oluşturun, ardından `install.php` dosyasını silin.
3. `admin/login.php` ile giriş yapın. Ön yüz yoktur; anketler yalnızca size verilen gizli bağlantı (`survey.php?t=...`) ile görülür.
4. Anket oluşturunca bağlantı "Panoya Kopyala" düğmesiyle alınır; sonuçlar yönetim panelinde ve CSV olarak alınır.
