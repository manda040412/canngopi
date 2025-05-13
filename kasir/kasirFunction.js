document.addEventListener('DOMContentLoaded', function() {
    // // Menyimpan dan mengambil Notes
    // const saveNoteButton = document.getElementById('save-note');
    // if (saveNoteButton) {
    //     saveNoteButton.addEventListener('click', function() {
    //         const noteContent = document.getElementById('note-input').value;
    //         localStorage.setItem('savedNote', noteContent);
    //         alert('Note saved!');
    //     });
    // }

    // // Catatan disimpan
    // const savedNote = localStorage.getItem('savedNote');
    // if (savedNote) {
    //     document.getElementById('note-input').value = savedNote;
    // }
    // verifikasi password pin

    //button list order
    const menuContainer = document.querySelector('.menu-container');
    let startX;
    let isSwiping = false;

    menuContainer.addEventListener('touchstart', function (e) {
        startX = e.touches[0].pageX;
        isSwiping = true;
    });

    menuContainer.addEventListener('touchmove', function (e) {
        if (!isSwiping) return;
        const currentX = e.touches[0].pageX;
        const deltaX = currentX - startX;

        if (deltaX < -50) { // Geser ke kiri
            menuContainer.classList.add('show-delete');
        } else if (deltaX > 50) { // Geser ke kanan
            menuContainer.classList.remove('show-delete');
        }
    });

    menuContainer.addEventListener('touchend', function () {
        isSwiping = false;
    });

    // Memfilter item berdasarkan pemilihan kategori
    const filterLinks = document.querySelectorAll('.sidebar a');
    const menuItems = document.querySelectorAll('.menu-item');
    filterLinks.forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const category = link.getAttribute('data-item');
            filterItems(category);
            filterLinks.forEach(l => l.parentElement.classList.remove('active'));
            link.parentElement.classList.add('active');
        });
    });

    function filterItems(category) {
        menuItems.forEach(item => {
            const itemCategory = item.getAttribute('data-category');
            item.style.display = (category === 'Semua' || itemCategory === category) ? 'flex' : 'none';
        });
    }

    filterItems('Semua');

    // Menambahkan event listener untuk tombol diskon
    document.querySelectorAll('.discount-btn').forEach(button => {
        button.addEventListener('click', function() {
            const discountId = this.getAttribute('data-discountid');
            const discountPercentage = this.getAttribute('data-persentase');

            // Menampilkan popup konfirmasi diskon
            showDiscountPopup(discountId, discountPercentage);
        });
    });

    // Fungsi untuk menampilkan popup konfirmasi diskon
    function showDiscountPopup(discountId, discountPercentage) {
        const discountInfo = document.getElementById("discount-info");
        discountInfo.textContent = `Apakah Anda yakin ingin menerapkan diskon ${discountPercentage}%?`;
        
        // Menampilkan popup
        document.getElementById("discount-popup").style.display = "flex";

        // Menangani klik tombol konfirmasi diskon
        document.getElementById("confirm-button-discount").addEventListener("click", function() {
            // Lakukan aksi untuk menerapkan diskon di sini, misalnya mengirimkan request ke server
            applyDiscount(discountId);
            document.getElementById("discount-popup").style.display = "none"; // Menyembunyikan popup
        });

        // Menangani klik tombol batal
        document.getElementById("cancel-button-discount").addEventListener("click", function() {
            document.getElementById("discount-popup").style.display = "none"; // Menyembunyikan popup
        });
    }

    // Handle closing the order option popup
    const closePopupButton = document.getElementById('close-popup');
    closePopupButton.addEventListener('click', function () {
        orderPopup.style.display = 'none'; // Close the order option popup
    });

    const categoryLinks = document.querySelectorAll('.sidebar ul li a');

    categoryLinks.forEach(link => {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            const selectedCategory = this.getAttribute('data-item');

            // Show all menu items if "All" is selected
            if (selectedCategory === 'All') {
                menuItems.forEach(item => {
                    item.classList.remove('hidden'); // Show all items
                });
            } else {
                // Hide or show items based on category
                menuItems.forEach(item => {
                    if (item.getAttribute('data-category') === selectedCategory) {
                        item.classList.remove('hidden'); // Show matching category
                    } else {
                        item.classList.add('hidden'); // Hide non-matching items
                    }
                });
            }
        });
    });

        const discountButtons = document.querySelectorAll('.discount-btn');

        discountButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Hapus kelas aktif dari semua tombol
                discountButtons.forEach(btn => btn.classList.remove('active'));

                // Tambah kelas aktif ke tombol yang diklik
                this.classList.add('active');

                // Ambil DiscountID dan persentase dari tombol yang diklik
                const discountID = this.getAttribute('data-discountid');
                const persentase = parseFloat(this.getAttribute('data-persentase'));

                // Kirim DiscountID ke server dengan AJAX
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "discount.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        console.log("Discount applied:", xhr.responseText);
                    }
                };
                xhr.send("DiscountID=" + discountID + "&persentase=" + persentase);

                // Opsional: Hitung ulang total harga dengan diskon
                const subtotalElement = document.querySelector('.subtotal');
                const discountElement = document.querySelector('.discount');
                const totalAmountElement = document.querySelector('.total-amount');
                const subtotal = parseFloat(subtotalElement.innerText.replace('Rp ', '').replace(/\./g, '').replace(',', '.'));
                const discountAmount = subtotal * (persentase / 100);
                const totalAmount = subtotal - discountAmount;
                
                discountElement.innerText = `- Rp ${discountAmount.toLocaleString('id-ID')}`;
                totalAmountElement.innerText = `Rp ${totalAmount.toLocaleString('id-ID')}`;
            });
        });
    
    // JavaScript untuk menghapus pesanan ongoing ketika pengguna kembali ke halaman ini
    window.addEventListener("pageshow", function(event) {
        if (event.persisted || (window.performance && window.performance.navigation.type === 2)) { 
            // 'event.persisted' mendeteksi halaman yang di-cache
            // 'window.performance.navigation.type === 2' mendeteksi navigasi kembali
            deleteOngoingOrder();
        }
    });

    function deleteOngoingOrder() {
        // Mengirimkan permintaan AJAX ke halaman yang sama untuk menghapus pesanan ongoing
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "", true); // Mengirim permintaan ke halaman ini
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                console.log("Pesanan ongoing berhasil dihapus");
            }
        };

        // Kirim permintaan dengan action delete_ongoing
        xhr.send("action=delete_ongoing");
    }

});
