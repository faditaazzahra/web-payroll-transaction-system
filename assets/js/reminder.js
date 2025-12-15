function checkReminders() {
    fetch('process/cek-reminder.php')
    .then(response => response.json())
    .then(data => {
        if (data.reminders && data.reminders.length > 0) {
            data.reminders.forEach(reminder => {
                const message = `
                    Reminder Booking Kendaraan!
                    
                    Plat Nomor: ${reminder.plat_nomor}
                    Merk: ${reminder.merk}
                    Warna: ${reminder.warna}
                    Jadwal: ${reminder.tanggal_booking} ${reminder.jam_booking}
                    No. HP: ${reminder.no_hp}
                `;

                // Tampilkan notifikasi menggunakan Sweetalert2
                Swal.fire({
                    title: 'Reminder Booking!',
                    html: message.replace(/\n/g, '<br>'),
                    icon: 'info',
                    confirmButtonText: 'OK',
                    timer: 10000,
                    timerProgressBar: true
                });

                // Opsional: Putar suara notifikasi
                const audio = new Audio('/assets/sounds/notification.mp3');
                audio.play();
            });
        }
    })
    .catch(error => console.error('Error:', error));
}

// Jalankan pengecekan setiap 1 menit
setInterval(checkReminders, 60000);

// Jalankan pengecekan saat halaman dimuat
document.addEventListener('DOMContentLoaded', checkReminders);