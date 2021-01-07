import sys   
import ifcopenshell
import csv

# ON!
# sys.stderr = sys.stdout

prefixes = {None: 1, 'EXA': 1e18, 'PETA': 1e15, 'TERA': 1e12, 'GIGA': 1e9, 'MEGA':
    1e6, 'KILO': 1e3, 'HECTO': 1e2, 'DECA': 1e1, 'DECI': 1e-1, 'CENTI':
                1e-2, 'MILLI': 1e-3, 'MICRO': 1e-6, 'NANO': 1e-9, 'PICO': 1e-12,
            'FEMTO': 1e-15, 'ATTO': 1e-18}


def getSIUnits(model):
    SIUnit_length_list, SIUnit_area_list, SIUnit_volume_list, SIUnit_mass_list = [], [], [], []
    for SIUnit in model.by_type("IfcSIUnit"):
        for SIUnit_name in SIUnit:
            if SIUnit_name == 'LENGTHUNIT':
                SIUnit_length_list.append([SIUnit.Prefix, SIUnit.Name])
            elif SIUnit_name == 'AREAUNIT':
                SIUnit_area_list.append([SIUnit.Prefix, SIUnit.Name])
            elif SIUnit_name == 'VOLUMEUNIT':
                SIUnit_volume_list.append([SIUnit.Prefix, SIUnit.Name])
            elif SIUnit_name == 'MASSUNIT':
                SIUnit_mass_list.append([SIUnit.Prefix, SIUnit.Name])

    return SIUnit_length_list, SIUnit_area_list, SIUnit_volume_list, SIUnit_mass_list


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


def property_finder_unit(ifc_element, property_set, property_name):
    for s in ifc_element.IsDefinedBy:
        if hasattr(s, 'RelatingPropertyDefinition'):
            if s.RelatingPropertyDefinition.Name == property_set:
                if hasattr(s.RelatingPropertyDefinition, 'HasProperties'):
                    for v in s.RelatingPropertyDefinition.HasProperties:
                        if v.Name == property_name:
                            return v.Unit
                elif hasattr(s.RelatingPropertyDefinition, 'Quantities'):
                    for v in s.RelatingPropertyDefinition.Quantities:
                        if v.Name == property_name:
                            for attr, value in vars(v).items():
                                if attr.endswith('Value'):
                                    #print(v)
                                    return v.Unit
    return None


def material_property_finder(material, property_set, property_name):
    if hasattr(material, 'HasProperties'):  # 2x3 ToDo: Materialpropertyset missing
        for e in material.HasProperties:
            if e.Name == property_set:
                for v in e.Properties:
                    if v.Name == property_name:
                        return v.NominalValue.wrappedValue
    return None


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
        self.layerthickness = None
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
            self.area, self.area_unit = self.getArea()
            if self.area_unit is None and self.area is not None:
                self.area_unit = SIUnit_area
            if self.area_unit and self.area:  # Umrechnung der Einheit auf SI-Einheit (ohne Präfix)
                self.area *= prefixes[self.area_unit[0]]
                self.area_unit = self.area_unit[1]

        except:
            print("ERROR in area CALCULATION", self.product)
        # if self.area_unit is None:

        # Material
        try:
            self.material, self.material_density = self.getMaterial()
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

        # Primary Mass
        try:
            if self.area_density is not None and self.volume is not None:
                self.primary_mass = self.volume * self.area_density
        except:
            print("ERROR in Primary Mass CALCULATION", self.product)

        # Layer Thickness
        try:
            self.layerthickness = self.getLayerThickness()
        except:
            print("ERROR in Layer Thickness CALCULATION", self.product)

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
            area_unit = property_finder_unit(p, "QTo_WallBaseQuantities", "NetSideArea")
            if area is None:
                return property_finder(self.product, "BaseQuantities", "NetSideArea"), property_finder_unit(self.product, "BaseQuantities", "NetSideArea")
            else:
                return area, area_unit

        def Window_Door():
            try:
                return self.product.OverallHeight * self.product.OverallWidth, None
            except:
                return None, None

        def Column():
            area = property_finder(self.product, "QTo_WallBaseQuantities", "GrossSurfaceArea")  # outersurfacearea, totalsurfacearea
            area_unit = property_finder_unit(self.product, "QTo_WallBaseQuantities", "GrossSurfaceArea")
            if area is None:
                return property_finder(self.product, "BaseQuantities", "GrossSurfaceArea"), property_finder_unit(self.product, "BaseQuantities", "GrossSurfaceArea")
            else:
                return area, area_unit

        def Covering():
            # not in this file
            area = property_finder(self.product, "QTo_WallBaseQuantities", "GrossSurfaceArea")
            area_unit = property_finder_unit(self.product, "QTo_WallBaseQuantities", "GrossSurfaceArea")
            if area is None:
                return property_finder(self.product, "BaseQuantities", "GrossSurfaceArea"), property_finder_unit(self.product, "BaseQuantities", "GrossSurfaceArea")
            else:
                return area, area_unit

        def Slab_Roof():
            area = property_finder(self.product, "QTo_WallBaseQuantities", "GrossArea")
            area_unit = property_finder_unit(self.product, "QTo_WallBaseQuantities", "GrossArea")
            if area is None:
                return property_finder(self.product, "BaseQuantities", "GrossArea"), property_finder_unit(self.product, "BaseQuantities", "GrossArea")
            else:
                return area, area_unit

        def ShadingDevice():
            # not in this file
            area = property_finder(self.product, "QTo_WallBaseQuantities", "NetArea")
            area_unit = property_finder_unit(self.product, "QTo_WallBaseQuantities", "NetArea")
            if area is None:
                return property_finder(self.product, "BaseQuantities", "NetArea"), property_finder_unit(self.product, "BaseQuantities", "NetArea")
            else:
                return area, area_unit

        switcher = {"IfcWall": Wall,
                    "IfcWallStandardCase": Wall,
                    "IfcDoor": Window_Door,
                    "IfcWindow": Window_Door,
                    "IfcColumn": Column,
                    "IfcCovering": Covering,
                    "IfcSlab": Slab_Roof,
                    "IfcRoof": Slab_Roof,
                    "IfcShadingDevice": ShadingDevice}

        func = switcher.get(self.product.is_a(), lambda: (None, None))
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

    def getLayerThickness(self):
        layerThickness_list = []
        for relAssociates in self.product.HasAssociations:
            if relAssociates.is_a('IfcRelAssociatesMaterial'):
                relatingMaterial = relAssociates.RelatingMaterial
                if relatingMaterial.is_a("IfcMaterialLayerSetUsage"):
                    for materialLayer in relatingMaterial.ForLayerSet.MaterialLayers:
                        layerThickness_list.append(materialLayer.LayerThickness)
                if relatingMaterial.is_a("IfcMaterialLayerSet"):
                    for materialLayer in relatingMaterial.MaterialLayers:
                        if materialLayer.is_a('IfcMaterialLayer'):
                            layerThickness_list.append(materialLayer.LayerThickness)
        return layerThickness_list

    def getMaterial(self):
        material_list = []
        densities = []
        for relAssociates in self.product.HasAssociations:
            if relAssociates.is_a('IfcRelAssociatesMaterial'):
                relatingMaterial = relAssociates.RelatingMaterial
                if relatingMaterial.is_a("IfcMaterial"):
                    material_list.append(relatingMaterial.Name)
                    densities.append(material_property_finder(relatingMaterial, 'Pset_MaterialCommon', 'MassDensity'))
                elif relatingMaterial.is_a("IfcMaterialConstituentSet"):
                    for materialConstituent in relatingMaterial.MaterialConstituents:
                        if materialConstituent.is_a("IfcMaterialConstituent"):
                            material_list.append(materialConstituent.Material.Name)
                            densities.append(material_property_finder(materialConstituent.Material, 'Pset_MaterialCommon', 'MassDensity'))
                elif relatingMaterial.is_a("IfcMaterialLayerSet"):
                    for materialLayer in relatingMaterial.MaterialLayers:
                        if materialLayer.is_a('IfcMaterialLayer'):
                            material_list.append(materialLayer.Material.Name)
                            densities.append(material_property_finder(materialLayer.Material, 'Pset_MaterialCommon', 'MassDensity'))
                elif relatingMaterial.is_a("IfcMaterialLayerSetUsage"):
                    for materialLayer in relatingMaterial.ForLayerSet.MaterialLayers:
                        if materialLayer.is_a('IfcMaterialLayer'):
                            material_list.append(materialLayer.Material.Name)
                            densities.append(material_property_finder(materialLayer.Material, 'Pset_MaterialCommon', 'MassDensity'))
                elif relatingMaterial.is_a("IfcMaterialProfileSet"):
                    for materialProfile in relatingMaterial.MaterialProfiles:
                        if materialProfile.is_a('IfcMaterialProfile'):
                            material_list.append(materialProfile.Material.Name)
                            densities.append(material_property_finder(materialProfile.Material, 'Pset_MaterialCommon', 'MassDensity'))
                elif relatingMaterial.is_a("IfcMaterialProfileSetUsage"):
                    for materialProfile in relatingMaterial.ForProfileSet.MaterialProfiles:
                        if materialProfile.is_a('IfcMaterialProfile'):
                            material_list.append(materialProfile.Material.Name)
                            densities.append(material_property_finder(materialProfile.Material, 'Pset_MaterialCommon', 'MassDensity'))
                elif relatingMaterial.is_a('IfcMaterialList'):
                    for materialList in relatingMaterial:
                        for material in materialList:
                            if material.is_a('IfcMaterial'):
                                material_list.append(material.Name)
                                densities.append(material_property_finder(material, 'Pset_MaterialCommon', 'MassDensity'))

        if len(densities) == 1:
            densities = densities[0]
        elif len(material_list) == 0:
            densities = None

        if len(material_list) == 1:
            return material_list[0], densities
        elif len(material_list) == 0:
            return "", densities
        else:
            material_list_string = str(material_list).replace("[", "")
            material_list_string = material_list_string.replace("]", "")
            material_list_string = material_list_string.replace(";", ",")
            return material_list_string, densities


# ON! 
if len(sys.argv) != 3:
   sys.exit('Keine korrekte Anzahl Argumente') 

# model = ifcopenshell.open("FachmodellTGA.ifc")
model = ifcopenshell.open(sys.argv[1])

schema = model.schema

SIUnit_length_list, SIUnit_area_list, SIUnit_volume_list, SIUnit_mass_list = getSIUnits(model)
#print(SIUnit_length_list, SIUnit_area_list, SIUnit_volume_list, SIUnit_mass_list)
if len(SIUnit_area_list) == 0:
    SIUnit_area = [None, 'SQUARE_METRE']
else:
    SIUnit_area = SIUnit_area_list[0]

#print(schema)
#print(SIUnit_area)

Produkte = []

for p in model.by_type("IfcProduct"):
    if p.is_a() in ["IfcVirtualElement", "IfcAnnotation", "IfcOpeningElement", "IfcSite", "IfcSpace"] or p.Representation is None:
        continue
    Produkte.append(eLCA_Produkt(p))

#with open('IFC_data_20200624.csv', mode='w') as file:
#    writer = csv.writer(file, delimiter=';', quotechar='"', quoting=csv.QUOTE_MINIMAL)
#    writer.writerow(['GUID', 'Name', 'Stockwerk', 'Klasse', 'Flaeche', 'Flaecheneinheit', 'Kostengruppe', 'Masse', 'Material', 'Art'])
#    for P in Produkte:
#        writer.writerow([P.guid, P.name, P.storey, P.type, P.area, P.area_unit, P.KG, P.primary_mass, P.material, P.enum])
        
with open(sys.argv[2], 'w', encoding='utf-8') as file:
    writer = csv.writer(file, delimiter=';', quotechar='"', quoting=csv.QUOTE_MINIMAL)
    writer.writerow(['Name','Kostengruppe','Flaeche','Masse','Typ','Stockwerk','Material','GUID','PredefinedType','Unit'])
    for P in Produkte:
        writer.writerow([P.name, str(P.KG), P.area, P.primary_mass, P.type, P.storey, P.material, P.guid, P.enum, P.area_unit])        
