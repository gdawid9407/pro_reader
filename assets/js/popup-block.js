wp.blocks.registerBlockType('pro-reader/popup', {
    title: 'PRO Reader Popup',
    icon: 'megaphone',
    category: 'widgets',
    attributes: {
        content: { type: 'string' },
        triggerScrollPercentEnable: { type: 'boolean' },
        triggerScrollPercent: { type: 'number' },
        triggerTime: { type: 'number' },
        triggerScrollUp: { type: 'boolean' }
    },
    edit: function () {
        return 'Blok testowy działa! Sprawdź konsolę.';
    },
    save: function () {
        return null; // Ważne dla bloku dynamicznego
    },
});

console.log('--- Ręcznie załadowany popup-block.js został wykonany! ---');