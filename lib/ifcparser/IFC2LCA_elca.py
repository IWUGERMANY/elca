import sys
import ifcopenshell
import csv

# ON!
# sys.stderr = sys.stdout

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


class eLCA_Produkt:
    def __init__(self, p):
        self.product = p
        self.guid = None
        self.name = None
        self.storey = None
        self.type = None
        self.area = None
        self.area_unit = None
        self.KG = None
        self.primary_mass = None
        self.material = None
        self.area_density = None
        self.enum = None
        self.volume = None
        self.getInfos()

    def getInfos(self):

        # Global Id
        self.guid = self.product.GlobalId

        # Name
        self.name = self.product.Name

        # Type
        self.type = self.product.is_a()

        # Storey
        try:
            self.storey = self.getStorey()
        except:
            print("ERROR in STOREY CALCULATION", self.product)

        # Area
        try:
            self.area = self.getArea()
        except:
            print("ERROR in area CALCULATION", self.product)
        # TODO Einheit mit rausgeben, momentan hardgecoded

        # Material
        try:
            self.material, self.area_density = self.getMaterial()
        except:
            print("ERROR in material CALCULATION", self.product)

        # Kostengruppe
        try:
            self.KG = self.getKG()
        except:
            print("ERROR in KG CALCULATION", self.product)

        # Enumeration Type
        try:
            self.enum = self.getType()
        except:
            print("ERROR in PredefinedType CALCULATION", self.product)

        # Volume
        try:
            self.volume = self.getVolume()
        except:
            print("ERROR in Volume CALCULATION", self.product)

        # Primary_Mass
        try:
            if self.area_density is not None and self.volume is not None:
                self.primary_mass = self.volume * self.area_density
        except:
            print("ERROR in Primary Mass CALCULATION", self.product)

        # Area Unit
        for u in model.by_type("IFCSIUNIT"):
            if u.UnitType == "AREAUNIT":
                unit = u.Name
                if unit == "SQUARE_METRE":
                    # TODO prefix wird noch nicht betrachtet
                    unit = "qm"
                break
        self.area_unit = unit

    def getStorey(self):
        try:
            for rel_contained in self.product.ContainedInStructure:
                return rel_contained.RelatingStructure.Name
        except:
            return None

    def getKG(self):
        def IfcWall():
            isLoadBearing = property_finder(self.product, "Pset_WallCommon", "LoadBearing")
            isExternal = property_finder(self.product, "Pset_WallCommon", "IsExternal")

            if hasattr(self.product, 'IsTypedBy'):
                if self.product.IsTypedBy:
                    if self.product.IsTypedBy[0].RelatingType.PredefinedType == 'ELEMENTEDWALL':
                        if isExternal:
                            return 337
                        else:
                            return 346
            else:  # 2x3
                for e in self.product.IsDefinedBy:
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
            isExternal = property_finder(self.product, "Pset_ColumnCommon", "IsExternal")

            if isExternal:
                return 333
            else:
                return 343

        def IfcDoor():
            isExternal = property_finder(self.product, "Pset_DoorCommon", "IsExternal")

            if isExternal:
                return 334
            else:
                return 344

        def IfcWindow():
            isExternal = property_finder(self.product, "Pset_WindowCommon", "IsExternal")
            if hasattr(p, 'IsTypedBy'):
                if self.product.IsTypedBy:
                    if self.product.IsTypedBy[0].RelatingType.PredefinedType in ['LIGHTDOME', 'SKYLIGHT']:
                        return 362
            else:  # 2x3
                for e in self.product.IsDefinedBy:
                    if hasattr(e, 'RelatingType'):
                        if e.RelatingType.ConstructionType in ['LIGHTDOME', 'SKYLIGHT']:
                            return 362
            if isExternal:
                return 334
            else:
                return 344

        def IfcCovering():
            # Belag oder Bekleidung
            if schema == "IFC4":
                if self.product.PredefinedType == 'CEILING':
                    return 352
                if self.product.PredefinedType == 'ROOFING':
                    return 363
                if self.product.PredefinedType == 'FLOORING':
                    return 325
                if self.product.PredefinedType == 'INSULATION':
                    wall = self.product.CoversElements.RelatingBuildingElement
                    isExternal = property_finder(wall, "Pset_WallCommon", "IsExternal")
                    if isExternal:
                        return 336
                    else:
                        return 345
            else:
                if self.product.PredefinedType == 'CEILING':
                    return 352
                if self.product.PredefinedType == 'ROOFING':
                    return 363
                if self.product.PredefinedType == 'FLOORING':
                    return 325
                if self.product.PredefinedType == 'INSULATION':
                    wall = self.product.Covers[0].RelatingBuildingElement
                    isExternal = property_finder(wall, "Pset_WallCommon", "IsExternal")
                    if isExternal:
                        return 336
                    else:
                        return 345

        def IfcSlab():
            if self.product.PredefinedType == 'ROOF':
                return 351
            if self.product.PredefinedType == 'BASESLAB':
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

        func = switcher.get(self.product.is_a(), lambda: None)
        return func()

    def getArea(self):
        def Wall():
            area = property_finder(p, "QTo_WallBaseQuantities", "NetSideArea")
            if area is None:
                return property_finder(self.product, "BaseQuantities", "NetSideArea")
            else:
                return area

        def Window_Door():
            try:
                return self.product.OverallHeight * self.product.OverallWidth
            except:
                return None

        def Column():
            area = property_finder(self.product, "QTo_WallBaseQuantities", "GrossSurfaceArea")  # outersurfacearea, totalsurfacearea
            if area is None:
                return property_finder(self.product, "BaseQuantities", "GrossSurfaceArea")
            else:
                return area

        def Covering():
            # not in this file
            area = property_finder(self.product, "QTo_WallBaseQuantities", "GrossSurfaceArea")
            if area is None:
                return property_finder(self.product, "BaseQuantities", "GrossSurfaceArea")
            else:
                return area

        def Slab_Roof():
            area = property_finder(self.product, "QTo_WallBaseQuantities", "GrossArea")
            if area is None:
                return property_finder(self.product, "BaseQuantities", "GrossArea")
            else:
                return area

        def ShadingDevice():
            # not in this file
            area = property_finder(self.product, "QTo_WallBaseQuantities", "NetArea")
            if area is None:
                return property_finder(self.product, "BaseQuantities", "NetArea")
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

        func = switcher.get(self.product.is_a(), lambda: None)
        return func()

    def getVolume(self):
        def Wall_Column_Covering_Slab_Roof():
            volume = property_finder(self.product, "QTo_WallBaseQuantities", "GrossVolume")
            if volume is None:
                return property_finder(self.product, "BaseQuantities", "GrossVolume")
            else:
                return volume

        def Window_Door_ShadingDevice():
            volume = property_finder(self.product, "QTo_WallBaseQuantities", "Volume")
            if volume is None:
                return property_finder(self.product, "BaseQuantities", "Volume")
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

        func = switcher.get(self.product.is_a(), lambda: None)
        return func()

    def getType(self):
        if hasattr(self.product, 'PredefinedType'):
            enum = self.product.PredefinedType
        else:
            enum = None
        if enum == None or enum == "NOTDEFINED":
            enum = "STANDARD"

        return enum

    def getMaterial(self):
        density = 0
        material = None
        for i in self.product.HasAssociations:

            if i.is_a("IfcRelAssociatesClassification"): continue  # TODO das noch betrachten

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
                        # print(set_e, set_e.get_info())
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

# model = ifcopenshell.open("FachmodellTGA.ifc")
model = ifcopenshell.open(sys.argv[1])

schema = model.schema
Produkte = []

for p in model.by_type("IfcProduct"):
    if p.is_a() in ["IfcVirtualElement", "IfcAnnotation", "IfcOpeningElement", "IfcSite", "IfcSpace"] or p.Representation is None:
        continue
    Produkte.append(eLCA_Produkt(p))

# with open('IFC_data.csv', mode='w') as file:
with open(sys.argv[2], 'w', encoding='utf-8') as file:
    writer = csv.writer(file, delimiter=';', quotechar='"', quoting=csv.QUOTE_MINIMAL)
    #writer.writerow(['GUID', 'Name', 'Stockwerk', 'Klasse', 'Flaeche', 'Flaecheneinheit', 'Kostengruppe', 'Masse', 'Material', 'Art'])
    #for P in Produkte:
    #    writer.writerow([P.guid, P.name, P.storey, P.type, P.area, P.area_unit, P.KG, P.primary_mass, P.material, P.enum])
    
    writer.writerow(['Name','Kostengruppe','Flaeche','Masse','Typ','Stockwerk','Material','GUID','PredefinedType','Unit'])
    for P in Produkte:
        writer.writerow([P.name, str(P.KG), P.area, P.primary_mass, P.type, P.storey, P.material, P.guid, P.enum, P.area_unit])
