// <!-- Promo Banner Modal -->
<!-- <div id="promoModal" class="promo-modal">
    <div class="promo-modal-overlay" onclick="closePromoModal()"></div>
    <div class="promo-modal-content">
        <button class="promo-modal-close" onclick="closePromoModal()" aria-label="Close">
            <i data-lucide="x" width="24" height="24"></i>
        </button>
        <div class="promo-banner">
            <img src="assets/images/banner-promo" alt="Promo Spesial">
        </div>
    </div>
</div> 

// <style>
// .promo-modal {
//     display: none;
//     position: fixed;
//     top: 0;
//     left: 0;
//     width: 100%;
//     height: 100%;
//     z-index: 9999;
//     animation: fadeIn 0.3s ease;
// }

// .promo-modal.active {
//     display: block;
// }

// .promo-modal-overlay {
//     position: absolute;
//     top: 0;
//     left: 0;
//     width: 100%;
//     height: 100%;
//     background: rgba(0, 0, 0, 0.75);
//     backdrop-filter: blur(4px);
// }

// .promo-modal-content {
//     position: absolute;
//     top: 50%;
//     left: 50%;
//     transform: translate(-50%, -50%);
//     max-width: 90vw;
//     max-height: 90vh;
//     animation: slideUp 0.4s ease;
// }

// .promo-modal-close {
//     position: absolute;
//     top: -15px;
//     right: -15px;
//     width: 40px;
//     height: 40px;
//     border-radius: 50%;
//     background: var(--white);
//     border: none;
//     cursor: pointer;
//     display: flex;
//     align-items: center;
//     justify-content: center;
//     box-shadow: 0 4px 12px rgba(0,0,0,0.2);
//     transition: all 0.2s ease;
//     z-index: 1;
//     color: var(--gray-700);
// }

// .promo-modal-close:hover {
//     transform: scale(1.1);
//     background: var(--primary-500);
//     color: white;
// }

// .promo-banner {
//     background: var(--white);
//     border-radius: var(--radius-xl);
//     overflow: hidden;
//     box-shadow: 0 20px 60px rgba(0,0,0,0.3);
// }

// .promo-banner img {
//     display: block;
//     width: 100%;
//     height: auto;
//     max-width: 500px;
//     max-height: 80vh;
//     object-fit: contain;
// }

// @keyframes fadeIn {
//     from {
//         opacity: 0;
//     }
//     to {
//         opacity: 1;
//     }
// }

// @keyframes slideUp {
//     from {
//         opacity: 0;
//         transform: translate(-50%, -40%);
//     }
//     to {
//         opacity: 1;
//         transform: translate(-50%, -50%);
//     }
// }

// /* Mobile Responsive */
// @media (max-width: 768px) {
//     .promo-modal-content {
//         max-width: 95vw;
//     }

//     .promo-banner img {
//         max-width: 100%;
//     }

//     .promo-modal-close {
//         top: -10px;
//         right: -10px;
//         width: 36px;
//         height: 36px;
//     }
// }
// </style>

// <script>
// // Function to show modal
// function showPromoModal() {
//     const modal = document.getElementById('promoModal');
//     modal.classList.add('active');
//     document.body.style.overflow = 'hidden';

    
// }

// // Function to close modal
// function closePromoModal() {
//     const modal = document.getElementById('promoModal');
//     modal.classList.remove('active');
//     document.body.style.overflow = '';
// }

// window.addEventListener('load', function() {
//     if (typeof lucide !== 'undefined') {
//         lucide.createIcons();
//     }
    
//     setTimeout(function() {
//         showPromoModal();
//     }, 1000);
// });

// document.addEventListener('keydown', function(e) {
//     if (e.key === 'Escape') {
//         closePromoModal();
//     }
// });
// </script>
