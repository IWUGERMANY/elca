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
        this.modelId = null;
        this.debug = args.debug || false;
        this.srcFile = args.src;

        this.initViewer = function (onLoadedCallback) {
            var self = this;

            var tree = new StaticTreeRenderer({
                domNode: "treeContainer"
            });
            tree.addModel({
                id: 1,
                src: this.srcFile + ".xml"
            });
            tree.build();

            tree.on("click", function (oid, selected) {
                // Clicking an explorer node fits the view to its object and selects
                // if (selected.length) {
                //     self.bimSurfer.viewFit({
                //         ids: selected,
                //         animate: true
                //     });
                // }
                self.bimSurfer.setSelection({
                    ids: selected,
                    clear: true,
                    selected: true
                });
            });

            this.dataRenderer = new MetaDataRenderer({
                domNode: "dataContainer"
            });
            this.dataRenderer.addModel({
                src: this.srcFile + '.xml'
            });

            this.bimSurfer = new BimSurfer({
                domNode: "viewerContainer"
            });

            // debugging
            window.bimSurfer = this.bimSurfer;

            this.bimSurfer.load({
                src: this.srcFile
            }).then(function (model) {
                self.modelId = model.id;

                // Really make sure everything is loaded.
                Utils.Delay(100).then(function () {

                    var scene = self.bimSurfer.viewer.scene;

                    var aabb = scene.worldBoundary.aabb;
                    var diag = xeogl.math.subVec3(aabb.slice(3), aabb, xeogl.math.vec3());
                    var modelExtent = xeogl.math.lenVec3(diag);

                    scene.camera.project.near = modelExtent / 1000.;
                    scene.camera.project.far = modelExtent * 100.;

                    //scene.camera.view.eye = [-1, -1, 5];
                    //scene.camera.view.up = [0, 0, 1];
					
                    // scene.camera.up = [0,0,1];
                    self.bimSurfer.viewFit({centerModel: true});
                    self.bimSurfer.viewer.scene.canvas.canvas.style.display = 'block';
					
					self.bimSurfer.setCamera({  
						type:"persp"
					});
					
                });

                onLoadedCallback.apply(self, [self.bimSurfer, model]);
            });

            this.bimSurfer.on('selection-changed', function (selected) {
                if (selected.objects.length > 0) {
                    self.updateIfcInfo(selected.objects[0]);
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
                oid.split("#")[1].substr(8, 36).replace(/-/g, ""));
        };

        this.convertGuidToOid = function (guid) {
            var uncompressedGuid = IfcGuid.fromCompressedToFull(guid);
            return this.modelId + "#product-" + uncompressedGuid + "-body.entity.0.0";
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
        };

        this.updateIfcInfo = function (oid) {
            if (!oid) {
                return;
            }

            this.dataRenderer.setSelected([this.convertOidToGuid(oid)]);
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
