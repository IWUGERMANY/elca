define([
    "../bimsurfer/src/BimSurfer",
    "../bimsurfer/src/MetaDataRenderer",
    "../bimsurfer/src/StaticTreeRenderer",
    "../bimsurfer/src/Utils",
    "../bimsurfer/lib/IfcGuid"
], function (BimSurfer, MetaDataRenderer, StaticTreeRenderer, Utils, IfcGuid) {

    function ElcaIfcViewer(args) {

        this.bimSurfer = null;
        this.dataRenderer = null;
        this.treeRenderer = null;
        this.modelId = null;
        this.debug = args.debug || false;
        this.srcFile = args.src;

        this.initViewer = function (onLoadedCallback) {
            var self = this;

            this.treeRenderer = new StaticTreeRenderer({
                domNode: "treeContainer",
                withVisibilityToggle: true
            });
            this.treeRenderer.addModel({
                id: 1,
                src: this.srcFile + ".xml"
            });
            this.treeRenderer.build();

            this.treeRenderer.on("click", function (oid, selected) {
                // Clicking an explorer node fits the view to its object and selects
                self.bimSurfer.setSelection({
                    ids: selected,
                    clear: true,
                    selected: true
                });

                self.updateIfcInfo(selected[0]);
            });
            this.treeRenderer.on("visibility-changed", function (params) {
                self.bimSurfer.setVisibility(params);
            });

            this.dataRenderer = new MetaDataRenderer({
                domNode: "dataContainer"
            });
            this.dataRenderer.addModel({
                src: this.srcFile + '.xml'
            });

            this.bimSurfer = new BimSurfer({
                domNode: "viewerContainer",
                engine: 'threejs'
            });

            // debugging
            window.bimSurfer = this.bimSurfer;

            this.bimSurfer.load({
                src: this.srcFile
            }).then(function (model) {
                self.modelId = model.id;

                if (self.bimSurfer.engine === 'xeogl') {
                    // Really make sure everything is loaded.
                    Utils.Delay(100).then(function () {
                        var scene = self.bimSurfer.viewer.scene;
                        var aabb = scene.worldBoundary.aabb;
                        var diag = xeogl.math.subVec3(aabb.slice(3), aabb, xeogl.math.vec3());
                        var modelExtent = xeogl.math.lenVec3(diag);

                        scene.camera.project.near = modelExtent / 1000.;
                        scene.camera.project.far = modelExtent * 100.;

                        scene.camera.view.eye = [-1, -1, 5];
                        scene.camera.view.up = [0, 0, 1];
                        self.bimSurfer.viewFit({centerModel: true});

                        self.bimSurfer.viewer.scene.canvas.canvas.style.display = 'block';
                    });
                }

                onLoadedCallback.apply(self, [self.bimSurfer, model]);
            });

            this.bimSurfer.on('selection-changed', function (selected) {
                if (selected.objects) {
                    selected = selected.objects;
                }

                if (selected.length > 0) {
                    self.updateIfcInfo(self.convertOidToGuid(selected[0]));
                }
            });
        };

        this.convertOidToGuid = function (oid) {
            // So, there are several options here, id can either be a glTF identifier,
            // in which case the id is a rfc4122 guid, or an annotation in which case
            // it is a compressed IFC guid.
            if (oid.substr(0, 12) === "Annotations:") {
                return oid.substr(12);
            }

            return IfcGuid.fromFullToCompressed(
                oid.replace(/-/g, ""));
        };

        this.convertGuidToOid = function (guid) {
            var uncompressedGuid = IfcGuid.fromCompressedToFull(guid);

            return "product-" + uncompressedGuid + "-body";
        };

        this.viewElement = function (guid) {
            if (!guid) {
                return;
            }

            var oid = this.convertGuidToOid(guid);
            this.log('Show element ' + oid + ' [' + guid + ']');

            var currentSelection = this.bimSurfer.getSelection();
            if (currentSelection.length > 0 && currentSelection[0] === oid) {
                console.log('Element ' + guid + ' is already selected. Nothing to select.');
                return;
            }

            this.bimSurfer.setSelection({
                ids: [oid],
                selected: true,
                clear: true
            });

            this.updateIfcInfo(guid);

            this.treeRenderer.setSelected([guid])
        };

        this.updateIfcInfo = function (oid) {
            if (!oid) {
                return;
            }

            this.dataRenderer.setSelected([oid]);
        };

        this.log = function () {
            if (this.debug) {
                console.debug.apply(null, arguments);
            }
        };

        this.initViewer(args.onLoadedCallback);
    }

    return ElcaIfcViewer;
});
