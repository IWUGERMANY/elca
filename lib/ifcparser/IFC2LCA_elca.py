import sys
import ifcopenshell
import csv

sys.stderr = sys.stdout


class eLCA_Produkt:
    def __init__(self):
        self.product = None
        self.guid = None
        self.name = None
        self.storey = None
        self.type = None
        self.area = None
        self.KG = None
        self.primary_mass = None
        self.material = None
        self.area_density = None


def getStorey(p):
    try:
        for rel_contained in p.ContainedInStructure:
            return rel_contained.RelatingStructure.Name
    except:
        return None


def property_finder(ifc_element, property_set, property_name):
    for s in ifc_element.IsDefinedBy:
        if hasattr(s, 'RelatingPropertyDefinition'):
            if s.RelatingPropertyDefinition.Name == property_set:
                if hasattr(s.RelatingPropertyDefinition, 'HasProperties'):
                    for v in s.RelatingPropertyDefinition.HasProperties:
                        if v.Name == property_name:
                            return v.NominalValue.wrappedValue
                elif hasattr(s.RelatingPropertyDefinition, 'Quantities'):
                    for v in s.RelatingPropertyDefinition.Quantities:
                        if v.Name == property_name:
                            for attr, value in vars(v).items():
                                if attr.endswith('Value'):
                                    return value
    return None


def material_property_finder(material, property_set, property_name):
    if hasattr(material, 'HasProperties'):  # 2x3 ToDo: Materialpropertyset missing
        for e in material.HasProperties:
            if e.Name == property_set:
                try:
                    for v in e.Properties:
                        if v.Name == property_name:
                            return v.NominalValue.wrappedValue
                # unknown attribute type error
                except RuntimeError:
                    pass
    return 0


def KG(p):
    def IfcWall():
        isLoadBearing = property_finder(p, "Pset_WallCommon", "LoadBearing")
        isExternal = property_finder(p, "Pset_WallCommon", "IsExternal")

        if hasattr(p, 'IsTypedBy'):
            if p.IsTypedBy:
                if p.IsTypedBy[0].RelatingType.PredefinedType == 'ELEMENTEDWALL':
                    if isExternal:
                        return 337
                    else:
                        return 346
        else:  # 2x3
            for e in p.IsDefinedBy:
                if hasattr(e, 'RelatingType'):
                    if e.RelatingType.ConstructionType == 'ELEMENTEDWALL':
                        return 362

        if isExternal:
            if isLoadBearing:
                return 331
            else:
                return 332
        else:
            if isLoadBearing:
                return 341
            else:
                return 342

    def IfcColumn():
        isExternal = property_finder(p, "Pset_ColumnCommon", "IsExternal")

        if isExternal:
            return 333
        else:
            return 343

    def IfcDoor():
        isExternal = property_finder(p, "Pset_DoorCommon", "IsExternal")

        if isExternal:
            return 334
        else:
            return 344

    def IfcWindow():
        isExternal = property_finder(p, "Pset_WindowCommon", "IsExternal")
        if hasattr(p, 'IsTypedBy'):
            if p.IsTypedBy:
                if p.IsTypedBy[0].RelatingType.PredefinedType in ['LIGHTDOME', 'SKYLIGHT']:
                    return 362
        else:  # 2x3
            for e in p.IsDefinedBy:
                if hasattr(e, 'RelatingType'):
                    if e.RelatingType.ConstructionType in ['LIGHTDOME', 'SKYLIGHT']:
                        return 362
        if isExternal:
            return 334
        else:
            return 344

    def IfcCovering():
        # Belag oder Bekleidung
        if p.PredefinedType == 'CEILING':
            return 352
        if p.PredefinedType == 'ROOFING':
            return 363
        if p.PredefinedType == 'FLOORING':
            return 325
        if p.PredefinedType == 'INSULATION':
            wall = p.RelatingBuildingElement
            isExternal = property_finder(wall, "Pset_WallCommon", "IsExternal")
            if isExternal:
                return 336
            else:
                return 345

    def IfcSlab():
        if p.PredefinedType == 'ROOF':
            return 351
        if p.PredefinedType == 'BASESLAB':
            return 324

    def IfcRoof():
        return 361

    def IfcShadingDevice():
        return 338

    switcher = {"IfcWall": IfcWall,
                "IfcWallStandardCase": IfcWall,
                "IfcColumn": IfcColumn,
                "IfcDoor": IfcDoor,
                "IfcWindow": IfcWindow,
                "IfcCovering": IfcCovering,
                "IfcSlab": IfcSlab,
                "IfcRoof": IfcRoof,
                "IfcShadingDevice": IfcShadingDevice}

    func = switcher.get(p.is_a(), lambda: None)
    return func()


def Area(p):
    def Wall():
        area = property_finder(p, "QTo_WallBaseQuantities", "NetSideArea")
        if area is None:
            print('1', area,property_finder(p, "BaseQuantities", "NetSideArea")) 
            return property_finder(p, "BaseQuantities", "NetSideArea")
        else:
            print('2', area) 
            return area

    def Window_Door():
        try:
            return p.OverallHeight * p.OverallWidth
        except:
            return None

    def Column():
        area = property_finder(p, "QTo_WallBaseQuantities", "GrossSurfaceArea")  # outersurfacearea, totalsurfacearea
        if area is None:
            return property_finder(p, "BaseQuantities", "GrossSurfaceArea")
        else:
            return area

    def Covering():
        # not in this file
        area = property_finder(p, "QTo_WallBaseQuantities", "GrossSurfaceArea")
        if area is None:
            return property_finder(p, "BaseQuantities", "GrossSurfaceArea")
        else:
            return area

    def Slab_Roof():
        area = property_finder(p, "QTo_WallBaseQuantities", "GrossArea")
        if area is None:
            return property_finder(p, "BaseQuantities", "GrossArea")
        else:
            return area

    def ShadingDevice():
        # not in this file
        area = property_finder(p, "QTo_WallBaseQuantities", "NetArea")
        if area is None:
            return property_finder(p, "BaseQuantities", "NetArea")
        else:
            return area

    switcher = {"IfcWall": Wall,
                "IfcWallStandardCase": Wall,
                "IfcDoor": Window_Door,
                "IfcWindow": Window_Door,
                "IfcColumn": Column,
                "IfcCovering": Covering,
                "IfcSlab": Slab_Roof,
                "IfcRoof": Slab_Roof,
                "IfcShadingDevice": ShadingDevice}

    func = switcher.get(p.is_a(), lambda: None)
    return func()


def Volume(p):
    def Wall_Column_Covering_Slab_Roof():
        volume = property_finder(p, "QTo_WallBaseQuantities", "GrossVolume")
        if volume is None:
            return property_finder(p, "BaseQuantities", "GrossVolume")
        else:
            return volume

    def Window_Door_ShadingDevice():
        volume = property_finder(p, "QTo_WallBaseQuantities", "Volume")
        if volume is None:
            return property_finder(p, "BaseQuantities", "Volume")
        else:
            return volume

    switcher = {"IfcWall": Wall_Column_Covering_Slab_Roof,
                "IfcWallStandardCase": Wall_Column_Covering_Slab_Roof,
                "IfcDoor": Window_Door_ShadingDevice,
                "IfcWindow": Window_Door_ShadingDevice,
                "IfcColumn": Wall_Column_Covering_Slab_Roof,
                "IfcCovering": Wall_Column_Covering_Slab_Roof,
                "IfcSlab": Wall_Column_Covering_Slab_Roof,
                "IfcRoof": Wall_Column_Covering_Slab_Roof,
                "IfcShadingDevice": Window_Door_ShadingDevice}

    func = switcher.get(p.is_a(), lambda: None)
    return func()


def Material(p):
    density = 0
    material = None
    for i in p.HasAssociations:
        if hasattr(i.RelatingMaterial, 'Name'):
            material = i.RelatingMaterial.Name
            density += material_property_finder(i.RelatingMaterial, 'Pset_MaterialCommon', 'MassDensity')
        # various materials (not layers)
        elif hasattr(i.RelatingMaterial, 'Materials'):
            materials = []
            for mat in i.RelatingMaterial.Materials:
                materials.append(mat.Name)
                density += material_property_finder(mat, 'Pset_MaterialCommon', 'MassDensity')
            if len(materials) == 1:
                material = materials[0]
            else:
                material = str(materials)
        # MaterialConstituentSet
        elif hasattr(i.RelatingMaterial, 'MaterialConstituents'):
            materials = []
            for mat in i.RelatingMaterial.MaterialConstituents.ToMaterialConstituentSet:
                materials.append(mat.Name)
                density += material_property_finder(mat, 'Pset_MaterialCommon', 'MassDensity')
            if len(materials) == 1:
                material = materials[0]
            else:
                material = str(materials)

        # materialsets
        for attr, value in vars(i.RelatingMaterial).items():
            # (layersetusage, profilesetusage)
            materials = []
            if attr.startswith('For'):
                for at, val in vars(value).items():
                    if at.startswith('Material'):
                        for set_e in val:
                            materials.append(set_e.Material.Name)
                            density += material_property_finder(set_e.Material, 'Pset_MaterialCommon',
                                                                'MassDensity')
                if len(materials) == 1:
                    material = materials[0]
                else:
                    material = str(materials)
            # (layerset, profileset)
            elif attr.startswith('Material'):
                for set_e in value:
                    # on deaktiviert 2020-04-22
                    #print(set_e, set_e.get_info()) 
                    materials.append(set_e.Name)
                    # materials.append(set_e.Material.Name)
                    # density += material_property_finder(set_e.Material, 'Pset_MaterialCommon','MassDensity')
                    if len(materials) == 1:
                        material = materials[0]
                    else:
                        material = str(materials)
    return material, density

# ON! 
if len(sys.argv) != 3:
   sys.exit('Keine korrekte Anzahl Argumente')    

#loc = locale.getlocale()
#print(loc)
#locale.setlocale(locale.LC_ALL, 'DE')
    
model = ifcopenshell.open(sys.argv[1])
Produkte = []

for p in model.by_type("IfcProduct"):
    if p.is_a() in ["IfcVirtualElement", "IfcAnnotation", "IfcOpeningElement", "IfcSite", "IfcSpace"]:
        continue

    if p.Representation is None:
        continue

    o = eLCA_Produkt()

    # Product
    o.product = p

    # Global Id
    o.guid = p.GlobalId

    # Name
    o.name = p.Name

    # Type
    o.type = p.is_a()

    # Storey
    o.storey = getStorey(p)

    # Area
    o.area = Area(p)
    # o.area = str(Area(p)).replace('.', ',')
    

    # Material
    o.material, o.area_density = Material(p)

    # Primary_Mass
    if o.area_density is not None and Volume(p) is not None:
        o.primary_mass = Volume(p) * o.area_density
        #o.primary_mass = str(Volume(p) * o.area_density).replace('.', ',')

    # Kostengruppe
    o.KG = KG(p)

    o.area_unit = "Stück"
    o.enum = "STANDARD"
    
    Produkte.append(o)


with open(sys.argv[2], 'w', encoding='utf-8') as file:
    writer = csv.writer(file, delimiter=';', quotechar='"', quoting=csv.QUOTE_MINIMAL)
    writer.writerow(['Name','Kostengruppe','Flaeche','Masse','Typ','Stockwerk','Material','GUID','PredefinedType','Unit'])
    for P in Produkte:
        writer.writerow([P.name, str(P.KG), P.area, P.primary_mass, P.type, P.storey, P.material, P.guid, P.enum, P.area_unit])


