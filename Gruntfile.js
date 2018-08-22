module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        compass: {
            options: {
                require: ['sass-globbing'],
                outputStyle: 'compact',
                noLineComments: true,
                relativeAssets: false,
                importPath: 'app/Elca/assets/sass/variables'
            },
            elca: {
                options: {
                    sassDir: 'app/Elca/assets/sass',
                    cssDir: 'www/css/elca'
                }
            },
            bnb: {
                options: {
                    sassDir: 'app/Bnb/assets/sass',
                    cssDir: 'www/css/bnb'
                }
            },
            nawoh: {
                options: {
                    sassDir: 'app/NaWoh/assets/sass',
                    cssDir: 'www/css/nawoh'
                }
            },
            lcc: {
                options: {
                    sassDir: 'app/Lcc/assets/sass',
                    cssDir: 'www/css/lcc'
                }
            },
            stlb: {
                options: {
                    sassDir: 'app/Stlb/assets/sass',
                    cssDir: 'www/css/stlb'
                }
            },
            soda4lca: {
                options: {
                    sassDir: 'app/Soda4Lca/assets/sass',
                    cssDir: 'www/css/soda4lca'
                }
            },
            importAssistant: {
                options: {
                    sassDir: 'app/ImportAssistant/assets/sass',
                    cssDir: 'www/css/importAssistant'
                }
            }
        },
        uglify: {
            options: {
                compress: {}, // debug
                beautify: false, // debug
                mangle: false
            },
            elca: {
                files: {
                    'www/js/elca/elca.min.js': [
                        'app/Elca/assets/js/jBlibs.js',
                        'app/Elca/assets/js/elca.js',
                        'app/Elca/assets/js/elca_charts.js',
                        'vendor/beibob/htmlTools/js/selectboxChooser.js'
                   ]
                }
            },
            jquery: {
                files: {
                    'www/js/jquery/jquery.min.js': [
                        'www/js/jquery/jquery-1.10.2.min.js',
                        'www/js/jquery/jquery-ui-1.9.2.custom.js',
                        'www/js/jquery/jquery.browser.min.js',
                        'www/js/jquery/jquery.numeric.js',
                        'www/js/jquery/js.cookie.js',
//                        'www/js/jquery/jquery.cookie.js',
                        'www/js/jquery/jquery.url.packed.js',
                        'www/js/jquery/jquery.ba-hashchange.js',
                        'www/js/jquery/jquery.tablescroll.js',
                        'www/js/jquery/jquery.visible.min.js',
                        'www/js/jquery/jquery.form.min.js',
                        'www/js/jquery/jquery.cycle2.min.js',
                        'www/js/jquery/jquery.easing.1.3.js',
                        'www/js/jquery/jquery.base64.js',
                    ]
                }
            },
            bnb: {
                files: {
                    'www/js/bnb/bnb.min.js': ['app/Bnb/assets/js/bnb.js']
                }
            },
            nawoh: {
                files: {
                    'www/js/nawoh/nawoh.min.js': ['app/NaWoh/assets/js/nawoh.js']
                }
            },
            lcc: {
                files: {
                    'www/js/lcc/lcc.min.js': ['app/Lcc/assets/js/lcc.js']
                }
            },
            soda4lca: {
                files: {
                    'www/js/soda4lca/soda4lca.min.js': ['app/Soda4Lca/assets/js/soda4lca.js']
                }
            },
            stlb: {
                files: {
                    'www/js/stlb/stlb.min.js': ['app/Stlb/assets/js/stlb.js']
                }
            },
            importAssistant: {
                files: {
                    'www/js/importAssistant/import_assistant.min.js': ['app/ImportAssistant/assets/js/import_assistant.js']
                }
            }
        },
        watch: {
            elca_css: {
                files: ['app/Elca/assets/sass/**/*.scss'],
                tasks: ['compass:elca']
            },
            elca_js: {
                files: ['app/Elca/assets/js/*.js'],
                tasks: ['uglify:elca']
            },
            soda4lca_css: {
                files: ['app/Soda4Lca/assets/sass/**/*.scss'],
                tasks: ['compass:soda4lca']
            },
            soda4lca_js: {
                files: ['app/Soda4Lca/assets/js/*.js'],
                tasks: ['uglify:soda4lca']
            },
            lcc_css: {
                files: ['app/Lcc/assets/sass/**/*.scss'],
                tasks: ['compass:lcc']
            },
            lcc_js: {
                files: ['app/Lcc/assets/js/*.js'],
                tasks: ['uglify:lcc']
            },
            bnb_css: {
                files: ['app/Bnb/assets/sass/**/*.scss'],
                tasks: ['compass:bnb']
            },
            bnb_js: {
                files: ['app/Bnb/assets/js/*.js'],
                tasks: ['uglify:bnb']
            },
            nawoh_css: {
                files: ['app/NaWoh/assets/sass/**/*.scss'],
                tasks: ['compass:nawoh']
            },
            nawoh_js: {
                files: ['app/NaWoh/assets/js/*.js'],
                tasks: ['uglify:nawoh']
            },
            stlb_css: {
                files: ['app/Stlb/assets/sass/**/*.scss'],
                tasks: ['compass:stlb']
            },
            stlb_js: {
                files: ['app/Stlb/assets/js/*.js'],
                tasks: ['uglify:stlb']
            },
            importAssistant_css: {
                files: ['app/ImportAssistant/assets/sass/**/*.scss'],
                tasks: ['compass:importAssistant']
            },
            importAssistant_js: {
                files: ['app/ImportAssistant/assets/js/*.js'],
                tasks: ['uglify:importAssistant']
            }
        }
    });

    // Load the plugins
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-compass');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // Default task(s).
    grunt.registerTask('default', ['compass', 'uglify', 'watch']);
};
