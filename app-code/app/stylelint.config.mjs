/** @type {import("stylelint").Config} */
export default {
    extends: ['stylelint-config-standard'],
    files: [
        'resources/assets/catalog/css/**/*.css',
    ],
    referenceFiles: [
        'resources/assets/catalog/css/**/*.css',
        'Modules/**/resources/assets/**/*.css',
    ],
    rules: {
        'at-rule-no-unknown': [
            true,
            {
                ignoreAtRules: ['apply', 'plugin', 'source', 'theme', 'utility'],
            },
        ],
        'no-unknown-custom-properties': true,
        'selector-class-pattern': null,
        'color-function-notation': ['modern', { 'disableFix': true }],
        'import-notation': 'string',
        'property-no-vendor-prefix': [
            true,
            { 'ignoreProperties': ['-webkit-appearance', '-moz-appearance'] }
        ],
        'custom-property-pattern': [
            "^([a-z][a-z0-9]*)(-[a-z0-9]+)*$|^([a-z][a-z0-9]*)(-[a-z0-9]+\\\\?%?)*$|^([a-z][a-z0-9]*)(-[a-z0-9]+\\\\\\.\\d+em)*$",
            {
                'message': "Expected custom property name to be kebab-case or with '%' sign or with 'em' - %s"
            }
        ]
    },
};
