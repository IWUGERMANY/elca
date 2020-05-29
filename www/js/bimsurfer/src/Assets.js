define([], function () {
    
    var oidCounter = 1;
    
    function Asset() {
        var registered = false;
        this.args = arguments;
        
        this.register = function(viewer) {
            // TODO: use buildNormals
            if (registered) return;
            registered = true;
            viewer.createGeometry.apply(viewer, this.args);
        };
        
        this.render = function(viewer, id, type, matrix) {
            viewer.createObject("Annotations", 1, oidCounter, id, [this.args[0]], type, matrix);
            oidCounter += 1;
        };
    };
    
    var ArrowOut = new Asset("ArrowOut"
    ,[-1.0, -1.0, -12.0, 1.0, -1.0, -12.0, -1.0, 1.0, -12.0, 1.0, 1.0, -12.0, -1.0, -1.0, -5.0, 1.0, -1.0, -5.0, -1.0, 1.0, -5.0, 1.0, 1.0, -5.0, -3.0, 3.0, -5.0, 0.0, 0.0, -1.0, -3.0, -3.0, -5.0, 3.0, -3.0, -5.0, 3.0, 3.0, -5.0, -1.0, 1.0, -12.0, -1.0, 1.0, -12.0, -1.0, -1.0, -12.0, -1.0, -1.0, -12.0, 1.0, -1.0, -12.0, 1.0, -1.0, -12.0, 1.0, 1.0, -12.0, 1.0, 1.0, -12.0, -1.0, 1.0, -5.0, -1.0, 1.0, -5.0, -1.0, -1.0, -5.0, -1.0, -1.0, -5.0, 1.0, -1.0, -5.0, 1.0, -1.0, -5.0, 1.0, 1.0, -5.0, 1.0, 1.0, -5.0, 3.0, -3.0, -5.0, 3.0, -3.0, -5.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, -3.0, 3.0, -5.0, -3.0, 3.0, -5.0, -3.0, -3.0, -5.0, -3.0, -3.0, -5.0, 3.0, 3.0, -5.0, 3.0, 3.0, -5.0]
    ,[0.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, -1.0, 0.0, 0.0, 0.0, -1.0, 0.0, -0.999969482421875, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, -0.999969482421875, 0.7999817132949829, 0.0, 0.599993884563446, -0.7999817132949829, 0.0, 0.599993884563446, 0.7999817132949829, 0.0, 0.599993884563446, 0.7999817132949829, 0.0, 0.599993884563446, 0.0, 0.999969482421875, 0.0, -1.0, 0.0, 0.0, -0.999969482421875, 0.0, 0.0, 0.0, -1.0, 0.0, 0.0, -0.999969482421875, 0.0, 1.0, 0.0, 0.0, 0.999969482421875, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, -1.0, 0.0, -0.999969482421875, 0.0, 0.0, 0.0, -1.0, 0.999969482421875, 0.0, 0.0, 0.0, 0.0, -1.0, 0.0, 0.999969482421875, 0.0, 0.0, 0.0, -1.0, 0.0, 0.0, -0.999969482421875, 0.0, -0.7999817132949829, 0.599993884563446, -0.7999817132949829, 0.0, 0.599993884563446, 0.0, -0.7999817132949829, 0.599993884563446, 0.0, 0.7999817132949829, 0.599993884563446, -0.7999817132949829, 0.0, 0.599993884563446, 0.0, 0.7999817132949829, 0.599993884563446, 0.0, 0.0, -0.999969482421875, 0.0, -0.7999817132949829, 0.599993884563446, 0.0, 0.0, -0.999969482421875, 0.0, 0.7999817132949829, 0.599993884563446]
    ,[0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0]
    ,[2, 3, 1, 13, 21, 27, 19, 7, 25, 17, 5, 23, 15, 4, 6, 11, 12, 9, 28, 38, 29, 34, 10, 31, 26, 29, 36, 22, 8, 38, 24, 36, 8, 39, 35, 33, 37, 30, 32, 0, 2, 1, 20, 13, 27, 18, 19, 25, 16, 17, 23, 14, 15, 6, 26, 28, 29, 24, 26, 36, 28, 22, 38, 22, 24, 8]
    );
    
    var ArrowIn = new Asset("ArrowIn"
    ,[-1.0, -1.0, 1.0, 1.0, -1.0, 1.0, -1.0, 1.0, 1.0, 1.0, 1.0, 1.0, -1.0, -1.0, 8.0, 1.0, -1.0, 8.0, -1.0, 1.0, 8.0, 1.0, 1.0, 8.0, -3.0, 3.0, 8.0, 0.0, 0.0, 12.0, -3.0, -3.0, 8.0, 3.0, -3.0, 8.0, 3.0, 3.0, 8.0, -1.0, 1.0, 1.0, -1.0, 1.0, 1.0, -1.0, -1.0, 1.0, -1.0, -1.0, 1.0, 1.0, -1.0, 1.0, 1.0, -1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, -1.0, 1.0, 8.0, -1.0, 1.0, 8.0, -1.0, -1.0, 8.0, -1.0, -1.0, 8.0, 1.0, -1.0, 8.0, 1.0, -1.0, 8.0, 1.0, 1.0, 8.0, 1.0, 1.0, 8.0, 3.0, -3.0, 8.0, 3.0, -3.0, 8.0, 0.0, 0.0, 12.0, 0.0, 0.0, 12.0, 0.0, 0.0, 12.0, -3.0, 3.0, 8.0, -3.0, 3.0, 8.0, -3.0, -3.0, 8.0, -3.0, -3.0, 8.0, 3.0, 3.0, 8.0, 3.0, 3.0, 8.0]
    ,[0.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, -1.0, 0.0, 0.0, 0.0, -1.0, 0.0, -0.999969482421875, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, -0.999969482421875, 0.7999817132949829, 0.0, 0.599993884563446, -0.7999817132949829, 0.0, 0.599993884563446, 0.7999817132949829, 0.0, 0.599993884563446, 0.7999817132949829, 0.0, 0.599993884563446, 0.0, 0.999969482421875, 0.0, -1.0, 0.0, 0.0, -0.999969482421875, 0.0, 0.0, 0.0, -1.0, 0.0, 0.0, -0.999969482421875, 0.0, 1.0, 0.0, 0.0, 0.999969482421875, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, -1.0, 0.0, -0.999969482421875, 0.0, 0.0, 0.0, -1.0, 0.999969482421875, 0.0, 0.0, 0.0, 0.0, -1.0, 0.0, 0.999969482421875, 0.0, 0.0, 0.0, -1.0, 0.0, 0.0, -0.999969482421875, 0.0, -0.7999817132949829, 0.599993884563446, -0.7999817132949829, 0.0, 0.599993884563446, 0.0, -0.7999817132949829, 0.599993884563446, 0.0, 0.7999817132949829, 0.599993884563446, -0.7999817132949829, 0.0, 0.599993884563446, 0.0, 0.7999817132949829, 0.599993884563446, 0.0, 0.0, -0.999969482421875, 0.0, -0.7999817132949829, 0.599993884563446, 0.0, 0.0, -0.999969482421875, 0.0, 0.7999817132949829, 0.599993884563446]
    ,[0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0]
    ,[2, 3, 1, 13, 21, 27, 19, 7, 25, 17, 5, 23, 15, 4, 6, 11, 12, 9, 28, 38, 29, 34, 10, 31, 26, 29, 36, 22, 8, 38, 24, 36, 8, 39, 35, 33, 37, 30, 32, 0, 2, 1, 20, 13, 27, 18, 19, 25, 16, 17, 23, 14, 15, 6, 26, 28, 29, 24, 26, 36, 28, 22, 38, 22, 24, 8]
    );
    
    var ArrowOutReversed = new Asset("ArrowOutReversed"
    ,[-1.0, -1.0, 12.0, 1.0, -1.0, 12.0, -1.0, 1.0, 12.0, 1.0, 1.0, 12.0, -1.0, -1.0, 5.0, 1.0, -1.0, 5.0, -1.0, 1.0, 5.0, 1.0, 1.0, 5.0, -3.0, 3.0, 5.0, 0.0, 0.0, 1.0, -3.0, -3.0, 5.0, 3.0, -3.0, 5.0, 3.0, 3.0, 5.0, -1.0, 1.0, 12.0, -1.0, 1.0, 12.0, -1.0, -1.0, 12.0, -1.0, -1.0, 12.0, 1.0, -1.0, 12.0, 1.0, -1.0, 12.0, 1.0, 1.0, 12.0, 1.0, 1.0, 12.0, -1.0, 1.0, 5.0, -1.0, 1.0, 5.0, -1.0, -1.0, 5.0, -1.0, -1.0, 5.0, 1.0, -1.0, 5.0, 1.0, -1.0, 5.0, 1.0, 1.0, 5.0, 1.0, 1.0, 5.0, 3.0, -3.0, 5.0, 3.0, -3.0, 5.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, -3.0, 3.0, 5.0, -3.0, 3.0, 5.0, -3.0, -3.0, 5.0, -3.0, -3.0, 5.0, 3.0, 3.0, 5.0, 3.0, 3.0, 5.0]
    ,[0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, -1.0, 0.0, 0.0, 0.0, -1.0, 0.0, -0.999969482421875, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.999969482421875, 0.7999817132949829, 0.0, -0.599993884563446, -0.7999817132949829, 0.0, -0.599993884563446, 0.7999817132949829, 0.0, -0.599993884563446, 0.7999817132949829, 0.0, -0.599993884563446, 0.0, 0.999969482421875, 0.0, -1.0, 0.0, 0.0, -0.999969482421875, 0.0, 0.0, 0.0, -1.0, 0.0, 0.0, -0.999969482421875, 0.0, 1.0, 0.0, 0.0, 0.999969482421875, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 1.0, 0.0, -0.999969482421875, 0.0, 0.0, 0.0, 1.0, 0.999969482421875, 0.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.999969482421875, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.999969482421875, 0.0, -0.7999817132949829, -0.599993884563446, -0.7999817132949829, 0.0, -0.599993884563446, 0.0, -0.7999817132949829, -0.599993884563446, 0.0, 0.7999817132949829, -0.599993884563446, -0.7999817132949829, 0.0, -0.599993884563446, 0.0, 0.7999817132949829, -0.599993884563446, 0.0, 0.0, 0.999969482421875, 0.0, -0.7999817132949829, -0.599993884563446, 0.0, 0.0, 0.999969482421875, 0.0, 0.7999817132949829, -0.599993884563446]
    ,[0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0]
    ,[2, 1, 3, 13, 27, 21, 19, 25, 7, 17, 23, 5, 15, 6, 4, 11, 9, 12, 28, 29, 38, 34, 31, 10, 26, 36, 29, 22, 38, 8, 24, 8, 36, 39, 33, 35, 37, 32, 30, 0, 1, 2, 20, 27, 13, 18, 25, 19, 16, 23, 17, 14, 6, 15, 26, 29, 28, 24, 36, 26, 28, 38, 22, 22, 8, 24]
    );
    
    var ArrowInOut = new Asset("ArrowInOut"
    ,[-1.0, -1.0, -8.0, 1.0, -1.0, -8.0, -1.0, 1.0, -8.0, 1.0, 1.0, -8.0, -1.0, -1.0, -5.0, 1.0, -1.0, -5.0, -1.0, 1.0, -5.0, 1.0, 1.0, -5.0, -3.0, 3.0, -5.0, 0.0, 0.0, -1.0, -3.0, -3.0, -5.0, 3.0, -3.0, -5.0, 3.0, 3.0, -5.0, -1.0, 1.0, -8.0, -1.0, 1.0, -8.0, -1.0, -1.0, -8.0, -1.0, -1.0, -8.0, 1.0, -1.0, -8.0, 1.0, -1.0, -8.0, 1.0, 1.0, -8.0, 1.0, 1.0, -8.0, -1.0, 1.0, -5.0, -1.0, 1.0, -5.0, -1.0, -1.0, -5.0, -1.0, -1.0, -5.0, 1.0, -1.0, -5.0, 1.0, -1.0, -5.0, 1.0, 1.0, -5.0, 1.0, 1.0, -5.0, 3.0, -3.0, -5.0, 3.0, -3.0, -5.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, -3.0, 3.0, -5.0, -3.0, 3.0, -5.0, -3.0, -3.0, -5.0, -3.0, -3.0, -5.0, 3.0, 3.0, -5.0, 3.0, 3.0, -5.0, -3.0, 3.0, -8.0, 0.0, 0.0, -12.0, -3.0, -3.0, -8.0, 3.0, -3.0, -8.0, 3.0, 3.0, -8.0, -1.0, 1.0, -8.0, -1.0, -1.0, -8.0, 1.0, -1.0, -8.0, 1.0, 1.0, -8.0, 3.0, -3.0, -8.0, 3.0, -3.0, -8.0, 0.0, 0.0, -12.0, 0.0, 0.0, -12.0, 0.0, 0.0, -12.0, -3.0, 3.0, -8.0, -3.0, 3.0, -8.0, -3.0, -3.0, -8.0, -3.0, -3.0, -8.0, 3.0, 3.0, -8.0, 3.0, 3.0, -8.0]
    ,[0.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, -1.0, 0.0, 0.0, 0.0, -1.0, 0.0, -1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 0.0, -0.999969482421875, 0.7999817132949829, 0.0, 0.599993884563446, -0.7999817132949829, 0.0, 0.599993884563446, 0.7999817132949829, 0.0, 0.599993884563446, 0.7999817132949829, 0.0, 0.599993884563446, 0.0, 1.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 0.0, 0.0, -1.0, 0.0, 0.0, -1.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, -1.0, 0.0, -1.0, 0.0, 0.0, 0.0, -1.0, 1.0, 0.0, 0.0, 0.0, 0.0, -1.0, 0.0, 1.0, 0.0, 0.0, 0.0, -1.0, 0.0, 0.0, -0.999969482421875, 0.0, -0.7999817132949829, 0.599993884563446, -0.7999817132949829, 0.0, 0.599993884563446, 0.0, -0.7999817132949829, 0.599993884563446, 0.0, 0.7999817132949829, 0.599993884563446, -0.7999817132949829, 0.0, 0.599993884563446, 0.0, 0.7999817132949829, 0.599993884563446, 0.0, 0.0, -0.999969482421875, 0.0, -0.7999817132949829, 0.599993884563446, 0.0, 0.0, -0.999969482421875, 0.0, 0.7999817132949829, 0.599993884563446, 0.0, 0.0, 0.999969482421875, 0.7999817132949829, 0.0, -0.599993884563446, -0.7999817132949829, 0.0, -0.599993884563446, 0.7999817132949829, 0.0, -0.599993884563446, 0.7999817132949829, 0.0, -0.599993884563446, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.999969482421875, 0.0, -0.7999817132949829, -0.599993884563446, -0.7999817132949829, 0.0, -0.599993884563446, 0.0, -0.7999817132949829, -0.599993884563446, 0.0, 0.7999817132949829, -0.599993884563446, -0.7999817132949829, 0.0, -0.599993884563446, 0.0, 0.7999817132949829, -0.599993884563446, 0.0, 0.0, 0.999969482421875, 0.0, -0.7999817132949829, -0.599993884563446, 0.0, 0.0, 0.999969482421875, 0.0, 0.7999817132949829, -0.599993884563446]
    ,[0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0]
    ,[2, 3, 1, 13, 21, 27, 19, 7, 25, 17, 5, 23, 15, 4, 6, 11, 12, 9, 28, 38, 29, 34, 10, 31, 26, 29, 36, 22, 8, 38, 24, 36, 8, 39, 35, 33, 37, 30, 32, 0, 2, 1, 20, 13, 27, 18, 19, 25, 16, 17, 23, 14, 15, 6, 26, 28, 29, 24, 26, 36, 28, 22, 38, 22, 24, 8, 43, 41, 44, 48, 49, 58, 54, 51, 42, 47, 56, 49, 45, 58, 40, 46, 40, 56, 59, 53, 55, 57, 52, 50, 47, 49, 48, 46, 56, 47, 48, 58, 45, 45, 40, 46]
    );
    
    var Sphere = new Asset("Sphere"
    ,[0.0, 0.0, -1.0, 0.7236073017120361, -0.5257253050804138, -0.44721952080726624, -0.276388019323349, -0.8506492376327515, -0.4472198486328125, -0.8944262266159058, 0.0, -0.44721561670303345, -0.276388019323349, 0.8506492376327515, -0.4472198486328125, 0.7236073017120361, 0.5257253050804138, -0.44721952080726624, 0.276388019323349, -0.8506492376327515, 0.4472198486328125, -0.7236073017120361, -0.5257253050804138, 0.44721952080726624, -0.7236073017120361, 0.5257253050804138, 0.44721952080726624, 0.276388019323349, 0.8506492376327515, 0.4472198486328125, 0.8944262266159058, 0.0, 0.44721561670303345, 0.0, 0.0, 1.0, -0.16245555877685547, -0.49999526143074036, -0.8506544232368469, 0.42532268166542053, -0.30901139974594116, -0.8506541848182678, 0.26286882162094116, -0.8090116381645203, -0.5257376432418823, 0.8506478667259216, 0.0, -0.5257359147071838, 0.42532268166542053, 0.30901139974594116, -0.8506541848182678, -0.525729775428772, 0.0, -0.8506516814231873, -0.6881893873214722, -0.49999693036079407, -0.5257362127304077, -0.16245555877685547, 0.49999526143074036, -0.8506544232368469, -0.6881893873214722, 0.49999693036079407, -0.5257362127304077, 0.26286882162094116, 0.8090116381645203, -0.5257376432418823, 0.9510578513145447, -0.30901262164115906, 0.0, 0.9510578513145447, 0.30901262164115906, 0.0, 0.0, -0.9999999403953552, 0.0, 0.5877856016159058, -0.8090167045593262, 0.0, -0.9510578513145447, -0.30901262164115906, 0.0, -0.5877856016159058, -0.8090167045593262, 0.0, -0.5877856016159058, 0.8090167045593262, 0.0, -0.9510578513145447, 0.30901262164115906, 0.0, 0.5877856016159058, 0.8090167045593262, 0.0, 0.0, 0.9999999403953552, 0.0, 0.6881893873214722, -0.49999693036079407, 0.5257362127304077, -0.26286882162094116, -0.8090116381645203, 0.5257376432418823, -0.8506478667259216, 0.0, 0.5257359147071838, -0.26286882162094116, 0.8090116381645203, 0.5257376432418823, 0.6881893873214722, 0.49999693036079407, 0.5257362127304077, 0.16245555877685547, -0.49999526143074036, 0.8506543636322021, 0.525729775428772, 0.0, 0.8506516814231873, -0.42532268166542053, -0.30901139974594116, 0.8506541848182678, -0.42532268166542053, 0.30901139974594116, 0.8506541848182678, 0.16245555877685547, 0.49999526143074036, 0.8506543636322021]
    ,[0.0, 0.0, -1.0, 0.7235938310623169, -0.5257118344306946, -0.44718772172927856, -0.2763756215572357, -0.8506424427032471, -0.44721823930740356, -0.8944059610366821, 0.0, -0.44718772172927856, -0.2763756215572357, 0.8506424427032471, -0.44721823930740356, 0.7235938310623169, 0.5257118344306946, -0.44718772172927856, 0.2763756215572357, -0.8506424427032471, 0.44721823930740356, -0.7235938310623169, -0.5257118344306946, 0.44718772172927856, -0.7235938310623169, 0.5257118344306946, 0.44718772172927856, 0.2763756215572357, 0.8506424427032471, 0.44721823930740356, 0.8944059610366821, 0.0, 0.44718772172927856, 0.0, 0.0, 1.0, -0.16245003044605255, -0.4999847412109375, -0.8506424427032471, 0.42530596256256104, -0.3089998960494995, -0.8506424427032471, 0.2628559172153473, -0.808984637260437, -0.5257118344306946, 0.8506424427032471, 0.0, -0.5257118344306946, 0.42530596256256104, 0.3089998960494995, -0.8506424427032471, -0.5257118344306946, 0.0, -0.8506424427032471, -0.6881618499755859, -0.4999847412109375, -0.5257118344306946, -0.16245003044605255, 0.4999847412109375, -0.8506424427032471, -0.6881618499755859, 0.4999847412109375, -0.5257118344306946, 0.2628559172153473, 0.808984637260437, -0.5257118344306946, 0.9510483145713806, -0.3089998960494995, 0.0, 0.9510483145713806, 0.3089998960494995, 0.0, 0.0, -1.0, 0.0, 0.5877559781074524, -0.809015154838562, 0.0, -0.9510483145713806, -0.3089998960494995, 0.0, -0.5877559781074524, -0.809015154838562, 0.0, -0.5877559781074524, 0.809015154838562, 0.0, -0.9510483145713806, 0.3089998960494995, 0.0, 0.5877559781074524, 0.809015154838562, 0.0, 0.0, 1.0, 0.0, 0.6881618499755859, -0.4999847412109375, 0.5257118344306946, -0.2628559172153473, -0.808984637260437, 0.5257118344306946, -0.8506424427032471, 0.0, 0.5257118344306946, -0.2628559172153473, 0.808984637260437, 0.5257118344306946, 0.6881618499755859, 0.4999847412109375, 0.5257118344306946, 0.16245003044605255, -0.4999847412109375, 0.8506424427032471, 0.5257118344306946, 0.0, 0.8506424427032471, -0.42530596256256104, -0.3089998960494995, 0.8506424427032471, -0.42530596256256104, 0.3089998960494995, 0.8506424427032471, 0.16245003044605255, 0.4999847412109375, 0.8506424427032471]
    ,[0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0, 0.2, 0.2, 0.2, 1.0]
    ,[0, 13, 12, 1, 13, 15, 0, 12, 17, 0, 17, 19, 0, 19, 16, 1, 15, 22, 2, 14, 24, 3, 18, 26, 4, 20, 28, 5, 21, 30, 1, 22, 25, 2, 24, 27, 3, 26, 29, 4, 28, 31, 5, 30, 23, 6, 32, 37, 7, 33, 39, 8, 34, 40, 9, 35, 41, 10, 36, 38, 38, 41, 11, 38, 36, 41, 36, 9, 41, 41, 40, 11, 41, 35, 40, 35, 8, 40, 40, 39, 11, 40, 34, 39, 34, 7, 39, 39, 37, 11, 39, 33, 37, 33, 6, 37, 37, 38, 11, 37, 32, 38, 32, 10, 38, 23, 36, 10, 23, 30, 36, 30, 9, 36, 31, 35, 9, 31, 28, 35, 28, 8, 35, 29, 34, 8, 29, 26, 34, 26, 7, 34, 27, 33, 7, 27, 24, 33, 24, 6, 33, 25, 32, 6, 25, 22, 32, 22, 10, 32, 30, 31, 9, 30, 21, 31, 21, 4, 31, 28, 29, 8, 28, 20, 29, 20, 3, 29, 26, 27, 7, 26, 18, 27, 18, 2, 27, 24, 25, 6, 24, 14, 25, 14, 1, 25, 22, 23, 10, 22, 15, 23, 15, 5, 23, 16, 21, 5, 16, 19, 21, 19, 4, 21, 19, 20, 4, 19, 17, 20, 17, 3, 20, 17, 18, 3, 17, 12, 18, 12, 2, 18, 15, 16, 5, 15, 13, 16, 13, 0, 16, 12, 14, 2, 12, 13, 14, 13, 1, 14]
    );
        
    function Manager() {
        
        this.ArrowIn = function() {
            return ArrowIn;
        };
        
        this.ArrowOut = function() {
            return ArrowOut;
        };
        
        this.ArrowInOut = function() {
            return ArrowInOut;
        };
        
        this.Sphere = function() {
            return Sphere;
        };
        
    };
    
    return Manager;
    
});