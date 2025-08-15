/**
 * Translation Test Script
 * Run this in browser console on a post edit page to test translation functionality
 */

console.log('=== Translation Debug Test ===');

// 1. Check if required objects exist
console.log('1. Checking required objects...');
if (typeof jQuery !== 'undefined') {
    console.log('✓ jQuery available');
} else {
    console.error('✗ jQuery not available');
}

if (typeof nexus_ai_wp_translator_ajax !== 'undefined') {
    console.log('✓ AJAX object available:', nexus_ai_wp_translator_ajax);
} else {
    console.error('✗ AJAX object not available');
}

if (typeof NexusAIWPTranslatorAdmin !== 'undefined') {
    console.log('✓ Admin object available');
} else {
    console.error('✗ Admin object not available');
}

// 2. Check if translation button exists
console.log('2. Checking translation button...');
const translateButton = document.getElementById('nexus-ai-wp-translate-post');
if (translateButton) {
    console.log('✓ Translation button found');
} else {
    console.error('✗ Translation button not found');
}

// 3. Check if meta box exists
console.log('3. Checking translation meta box...');
const metaBox = document.querySelector('#nexus-ai-wp-translator-translation');
if (metaBox) {
    console.log('✓ Translation meta box found');
} else {
    console.error('✗ Translation meta box not found');
}

// 4. Test AJAX endpoint
console.log('4. Testing AJAX endpoint...');
if (typeof nexus_ai_wp_translator_ajax !== 'undefined') {
    jQuery.post(nexus_ai_wp_translator_ajax.ajax_url, {
        action: 'nexus_ai_wp_test_api',
        nonce: nexus_ai_wp_translator_ajax.nonce
    })
    .done(function(response) {
        console.log('✓ AJAX endpoint responsive:', response);
    })
    .fail(function(xhr, status, error) {
        console.error('✗ AJAX endpoint failed:', status, error);
    });
}

// 5. Simulate translation button click
console.log('5. Testing translation button click...');
if (translateButton && typeof jQuery !== 'undefined') {
    jQuery(translateButton).trigger('click');
    console.log('✓ Translation button click triggered');
} else {
    console.error('✗ Cannot trigger translation button click');
}

// 6. Check for event listeners
console.log('6. Checking event listeners...');
if (translateButton) {
    const listeners = getEventListeners ? getEventListeners(translateButton) : 'getEventListeners not available';
    console.log('Button event listeners:', listeners);
}

console.log('=== Debug Test Complete ===');
console.log('Check the results above for any errors.');