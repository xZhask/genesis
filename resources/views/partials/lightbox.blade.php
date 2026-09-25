{{-- Visor de fotos (resources/js/lightbox.js). Acompaña a un [data-gallery]. --}}
<dialog class="lightbox" data-lightbox aria-label="Foto ampliada">
    <button type="button" class="lb-btn close" data-lightbox-close aria-label="Cerrar"><x-icon name="close" /></button>
    <button type="button" class="lb-btn prev" data-lightbox-prev aria-label="Foto anterior"><x-icon name="chevron-left" /></button>
    <figure>
        <div class="frame" data-lightbox-frame></div>
        <figcaption class="cap" data-lightbox-caption aria-live="polite"></figcaption>
    </figure>
    <button type="button" class="lb-btn next" data-lightbox-next aria-label="Foto siguiente"><x-icon name="chevron-right" /></button>
</dialog>
