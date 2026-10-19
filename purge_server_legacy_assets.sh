#!/usr/bin/env bash
# ==============================================================================
# PEMIRA FMIPA — HOSTINGER SERVER ASSET PURGE SCRIPT
# Safely purges verified obsolete legacy files (>300 KB) from public/
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PUBLIC_DIR="${SCRIPT_DIR}/public"

echo "=== PURGING PEMIRA OBSOLETE ASSETS FROM SERVER DISK ==="
echo "Target: ${PUBLIC_DIR}"

FILES_TO_REMOVE=(
    "images/foto-slide-hero/hero-image-3.jpg"
    "images/foto-slide-hero/hero-image-2.jpg"
    "images/foto-slide-hero/hero-image-1.jpg"
    "images/foto-slide-hero/IMG_1.webp"
    "images/foto-slide-hero/IMG_2.webp"
    "images/foto-slide-hero/IMG_3.webp"
    "StarfieldCanvas.png"
    "images/background-logo-dpm.png"
    "images/foto-kegiatan/pemilihan-ketua-wakil-bem.png"
    "images/foto-kegiatan/pemilihan-ketua-himafi.png"
    "images/foto-kegiatan/pemilihan-ketua-himafarma.png"
    "images/foto-kegiatan/pemilihan-ketua-himabio.png"
    "images/foto-kegiatan/pemilihan-ketua-himaif.png"
    "images/foto-kegiatan/pemilihan-ketua-himatika.png"
    "images/foto-kegiatan/pemilihan-ketua-himaki.png"
    "Logo pemira.webp"
    "group-188.png"
    "logo-pemira.svg"
)

TOTAL_PURGED=0

for file in "${FILES_TO_REMOVE[@]}"; do
    TARGET="${PUBLIC_DIR}/${file}"
    if [ -f "${TARGET}" ]; then
        rm -f "${TARGET}"
        echo " [PURGED] ${file}"
        TOTAL_PURGED=$((TOTAL_PURGED + 1))
    fi
done

# Remove empty legacy directory
if [ -d "${PUBLIC_DIR}/images/foto-slide-hero" ]; then
    rmdir "${PUBLIC_DIR}/images/foto-slide-hero" 2>/dev/null || true
    echo " [CLEANED] images/foto-slide-hero directory"
fi

echo "=== PURGE COMPLETE: ${TOTAL_PURGED} files removed ==="
