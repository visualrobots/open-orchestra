module.exports = {
    application : {
        bundles: [
            'openorchestrabackoffice',
            'openorchestrauseradmin',
            'openorchestramediaadmin'
        ],
        dest: {
            template : 'web/built/', //web/build/template/template.js
            navigation : 'web/built/', //web/build/navigation/navigation.js
            javascript : 'web/built/openorchestra/'
        }
    }
};
