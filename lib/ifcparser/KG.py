# -*- coding: utf-8 -*-

# ----- Referenzierungen ----- #
referenceForExternalWall = "AW"
referenceForInternalWall = "IW"
referenceRaisedFloor = "BOa"
referenceForGutter = "Gutter"
referenceWasteWater = "S_Schmutzwasser"
referenceSprinkler = "B_"
referenceGas = "G_"
referenceHeating = "H_"
referenceCooling = "K_"
referenceWater = "TW_"
referenceWater2 = "S_T"
referenceRainWater = "S_Regenwasser"
referenceVentilation = "L_"
referenceVentilation2 = "luft"
referenceVentilation3 = "LUFT"


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


def distribution_system_finder(ifc_element):
    for s in ifc_element.HasAssignments:
        return s.RelatingGroup.ObjectType


def getKG(self):
    # ----- IfcBuildingElement: ----- #
    # Balken / Unterzug:
    def IfcBeam():
        isExternal = property_finder(self.product, "Pset_BeamCommon", "IsExternal")
        if isExternal is not None:
            if isExternal:
                return 333
                # Außenstützen
            elif not isExternal:
                return 343
                # Innenstützen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Bauteil / Bauelement
    def IfcBuildingElementProxy():
        return 300
        # Bauwerk Baukonstruktionen

    # Schornstein
    def IfcChimney():
        return 399
        # onstiges zur KG 390: Sonstige Maßnahmen für Baukonstruktionen

    # Stütze / Pfeiler
    def IfcColumn():
        isExternal = property_finder(self.product, "Pset_ColumnCommon", "IsExternal")
        isLoadBearing = property_finder(self.product, "Pset_ColumnCommon", "LoadBearing")
        reference = property_finder(self.product, "Pset_ColumnCommon", "Reference")
        if isExternal is not None:
            if isLoadBearing is not None:
                if isExternal and isLoadBearing:
                    return 333
                    # Außenstützen
                elif not isExternal and isLoadBearing:
                    return 343
                    # Innenstützen
                elif not isExternal and not isLoadBearing:
                    return 343
                    # Innenstützen
                elif isExternal and not isLoadBearing:
                    return 335
                    # Außenwandbekleidung Außen
                    # = nichttragende Stützen, die zum Zweck der Bekleidung modelliert werden
                elif not isExternal and not isLoadBearing and referenceRaisedFloor in reference:
                    return 353
                    # Deckenbeläge
                    # = Stützen des Doppelbodensystems
            elif isExternal:
                return 330
                # Außenwände/Vertikale Baukonstruktionen, außen
            elif not isExternal:
                return 340
                # Innenwände/Vertikale Baukonstruktionen, innen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Bekleidung / Belag
    # es handelt sich in diesem Fall um horizontale Bauteile. Vertikale Bekleidungen sollten in Revit
    # mit dem Werzeug Wand modelliert werden und können nicht als IfcCoverung exportiert werden.
    def IfcCovering():
        isExternal = property_finder(self.product, "Pset_CoveringCommon", "IsExternal")
        ''' TypeEnumeration:
            CEILING
            FLOORING
            CLADDING
            ROOFING
            MOLDING
            SKIRTINGBOARD
            INSULATION
            MEMBRANE
            SLEEVING
            WRAPPING
            USERDEFINED
            NOTDEFINED
        '''
        if self.enum == "CEILING":
            return 354
            # Deckenbekleidungen
        elif self.enum == "ROOFING":
            return 364
            # Dachbekleidung
        elif self.enum == "FLOORING":
            return 353
        if isExternal is not None:
            if self.enum == "CLADDING" and isExternal:
                return 335
                # Außenwandbekleidung, außen
            elif self.enum == "CLADDING" and not isExternal:
                return 336
                # Außenwandbekleidung, innen
            elif self.enum == "MOLDING" and isExternal:
                return 339
                # Sonstiges zur KG 330: Außenwände/Vertikale Baukonstruktionen, außen
            elif self.enum == "MOLDING" and not isExternal:
                return 349
                # Sonstiges zur KG 340: Innenwände/Vertikale Baukonstruktionen, innen
            elif self.enum == "SKIRTINGBOARD" and not isExternal:
                return 349
                # Sonstiges zur KG 340: Innenwände/Vertikale Baukonstruktionen, innen
            elif self.enum == "INSULATION" and isExternal:
                return 325
                # Abdichtungen und Bekleidungen der Gründung
            elif self.enum == "INSULATION" and not isExternal:
                return 354
                # Deckenbekleidung
            elif self.enum == "MEMBRANE" and isExternal:
                return 325
                # Abdichtungen und Bekleidungen der Gründung
            elif self.enum == "MEMBRANE" and not isExternal:
                return 354
                # Deckenbekleidung
            elif isExternal:
                return 330
                # Außenwände/Vertikale Baukonstruktionen, außen
            elif not isExternal:
                return 340
                # Innenwände/Vertikale Baukonstruktionen, innen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Vorhangfassade
    def IfcCurtainWall():
        isExternal = property_finder(self.product, "Pset_CurtainWallCommon", "IsExternal")
        if isExternal is not None:
            if isExternal:
                return 337
                # Elementierte Außenwandkonstruktionen
            elif not isExternal:
                return 346
                # Elementierte Innenwandkonstruktionen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Tür
    def IfcDoor():
        isExternal = property_finder(self.product, "Pset_DoorCommon", "IsExternal")
        if isExternal is not None:
            if isExternal:
                return 334
                # Außenwandöffnungen
            elif not isExternal:
                return 344
                # Innenwandöffnungen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Fundament
    def IfcFooting():
        return 322
        # Flachgründungen und Bodenplatten

    # Stab / Stabträger
    def IfcMember():
        isExternal = property_finder(self.product, "Pset_MemberCommon", "IsExternal")
        if isExternal is not None:
            if isExternal:
                return 337
                # Elementierte Außenwandkonstruktionen
            elif not isExternal:
                return 346
                # Elementierte Innenwandkonstruktionen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Fundament / Tiefgründung
    def IfcPile():
        return 323
        # Tiefgründungen

    # Platte / Paneel
    def IfcPlate():
        isExternal = property_finder(self.product, "Pset_PlateCommon", "IsExternal")
        if isExternal is not None:
            if isExternal:
                return 337
                # Elementierte Außenwandkonstruktionen
            elif not isExternal:
                return 346
                # Elementierte Innenwandkonstruktionen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Geländer
    def IfcRailing():
        isExternal = property_finder(self.product, "Pset_RailingCommon", "IsExternal")
        if isExternal is not None:
            if not isExternal:
                return 359
                # Sonstiges zur KG Decken/Horizontale Baukonstruktionen
            elif isExternal:
                return 369
                # Sonstiges zur KG Dächer
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Rampe
    def IfcRamp():
        return 351
        # Deckenkonstruktionen

    # Rampenlauf
    def IfcRampFlight():
        return 351
        # Deckenkonstruktionen

    # Dach
    def IfcRoof():
        isLoadBearing = property_finder(self.product, "Pset_RoofCommon", "LoadBearing")
        if isLoadBearing is not None:
            if isLoadBearing:
                return 361
                # Dachkonstuktionen
            elif not isLoadBearing:
                return 363
                # Dachbeläge
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Sonnenschutz
    def IfcShadingDevice():
        isExternal = property_finder(self.product, "Pset_ShadingDeviceCommon", "IsExternal")
        if isExternal is not None:
            if isExternal:
                return 338
                # Lichtschutz zur KG 330: Außenwände/Vertikale Baukonstruktionen, außen
            elif not isExternal:
                return 347
                # Lichtschutz zur KG 340: Innenwände/Vertikale Baukonstruktionen, innen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Decke / Dachfläche / Bodenplatte
    def IfcSlab():
        isExternal = property_finder(self.product, "Pset_SlabCommon", "IsExternal")
        isLoadBearing = property_finder(self.product, "Pset_SlabCommon", "LoadBearing")
        ''' TypeEnumeration:
            FLOOR
            ROOF
            LANDING
            BASESLAB
            USERDEFINED
            NOTDEFINED
        '''
        if self.enum == "BASESLAB":
            return 322
            # Flachgründungen und Bodenplatten
        elif self.enum == "LANDING":
            return 351
            # Dachkonstruktionen
        elif isLoadBearing is not None:
            if self.enum == "ROOF" and isLoadBearing:
                return 361
                # Dachkonstruktionen
            elif self.enum == "ROOF" and not isLoadBearing:
                return 363
                # Dachbeläge
            elif isExternal is not None:
                if self.enum == "FLOOR" or (isLoadBearing and not isExternal):
                    return 351
                    # Deckenkonstruktionen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Treppe
    def IfcStair():
        return 351
        # Dachkonstruktionen

    # Treppenlauf
    def IfcStairFlight():
        return 351
        # Dachkonstruktionen

    # Wand
    def IfcWall():
        reference = property_finder(self.product, "Pset_WallCommon", "Reference")
        isExternal = property_finder(self.product, "Pset_WallCommon", "IsExternal")
        isLoadBearing = property_finder(self.product, "Pset_WallCommon", "LoadBearing")
        isExtendToStructure = property_finder(self.product, "Pset_WallCommon", "ExtendToStructure")
        if isExternal is not None:
            if isLoadBearing is not None:
                if isExtendToStructure is not None:
                    if isLoadBearing and isExternal and not isExtendToStructure:
                        return 331
                        # Tragende Außenwände
                    elif not isLoadBearing and isExternal and not isExtendToStructure:
                        return 332
                        # Nichttragende Außenwände
                    elif not isLoadBearing and isExternal and isExtendToStructure:
                        return 335
                        # Außenwandbekleidung, außen
                    elif not isLoadBearing and not isExternal and (isExtendToStructure and referenceForExternalWall in reference):
                        return 336
                        # Außenwandbekleidung, innen
                    elif isLoadBearing and not isExternal and not isExtendToStructure:
                        return 341
                        # Tragende Innenwände
                    elif not isLoadBearing and not isExternal and not isExtendToStructure:
                        return 342
                        # Nichttragende Innenwände
                    elif not isLoadBearing and not isExternal and (isExtendToStructure and referenceForInternalWall in reference):
                        return 345
                        # Innenwandbekleidung
                    elif not isLoadBearing and not isExternal and isExtendToStructure:
                        return 000
            elif isExternal:
                return 330
                # Außenwände/Vertikale Baukonstruktionen, außen
            elif not isExternal:
                return 340
                # Innenwände/Vertikale Baukonstruktionen, innen
        else:
            return 300
            # Bauwerk Baukonstruktionen

    # Fenster
    def IfcWindow():
        isExternal = property_finder(self.product, "Pset_WindowCommon", "IsExternal")
        ''' TypeEnumeration:
            WINDOW
            SKYLIGHT
            LIGHTDOME
            USERDEFINED
            NOTDEFINED            
        '''
        if isExternal is not None:
            if self.enum == "LIGHTDOME" and isExternal:
                return 362
                # Dachöffnungen
            elif self.enum == "SKYLIGHT" and isExternal:
                return 362
                # Dachöffnungen
            elif isExternal:
                return 334
                # Außenwandöffnungen
            elif not isExternal:
                return 344
                # Innenwandöffnungen
        else:
            return 300

    # ----- IfcDistributionControlElement: ----- #
    # Aktor:
    def IfcActuator():
        return 480
        # Gebäude- und Anlagenautomation

    # Alarm / Gefahrenmelder
    def IfcAlarm():
        return 480
        # Gebäude- und Anlagenautomation

    # Regler
    def IfcController():
        return 480
        # Gebäude- und Anlagenautomation

    # Messinstrument
    def IfcFlowInstrument():
        return 480
        # Gebäude- und Anlagenautomation

    # Sicherungsschalter
    def IfcProtectiveDeviceTrippingUnit():
        return 480
        # Gebäude- und Anlagenautomation

    # Sensor
    def IfcSensor():
        return 480
        # Gebäude- und Anlagenautomation

    # Einheitsregler
    def IfcUnitaryControlElement():
        return 480
        # Gebäude- und Anlagenautomation

    # ----- IfcDistributionFlowElement: ----- #
    # Schacht / Graben / Revisionsschacht:
    def IfcDistributionChamberElement():
        return 399
        # Sonstiges zur KG 390: Sonstige Maßnahmen für Baukonstruktionen

    # ----- IfcEnergyConversionDevice: ----- #
    # Wärmerückgewinner:
    def IfcAirToAirHeatRecovery():
        return 430
        # Raumlufttechnische Anlagen

    # Heizkessel
    def IfcBoiler():
        return 421
        # Wärmeerzeugungsanlagen

    # Brenner
    def IfcBurner():
        return 421
        # Wärmeerzeugungsanlagen

    # Kältemaschine
    def IfcChiller():
        return 434
        # Költeanlagen

    # Heiz-Kühlelemente
    def IfcCoil():
        if self.system is not None:
            if referenceHeating in self.system:
                return 421
                # Wärmeerzeugungsanlagen
            elif referenceVentilation in self.system or referenceVentilation2 in self.system or referenceVentilation3 in self.system:
                return 430
                # Raumlufttechnische Analgen
            elif referenceCooling in self.system:
                return 434
                # Kälteanlagen
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # Kondensator
    def IfcCondenser():
        if self.system is not None:
            if referenceWasteWater in self.system:
                return 411
                # Abwasseranlagen
            elif referenceWater in self.system or referenceWater2 in self.system:
                return 412
                # Wasseranlagen
            elif referenceHeating in self.system:
                return 422
                # Wärmeverteilnetze
            elif referenceCooling in self.system:
                return 434
                # Kälteanlagen
            elif referenceSprinkler in self.system:
                return 474
                # Feuerlöschanlagen
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # Kühlbalken
    def IfcCooledBeam():
        return 434
        # Kälteanlagen

    # Kühlturm
    def IfcCoolingTower():
        return 434
        # Kälteanlagen

    # Elektrogenerator
    def IfcElectricGenerator():
        return 442
        # Eigenstromversorgungsanlagen

    # Elektromotor
    def IfcElectricMotor():
        return 400
        # Bauwerk — Technische Anlagen

    # Motor
    def IfcEngine():
        return 400
        # Bauwerk — Technische Anlagen

    # Verdunstungskühler
    def IfcEvaporativeCooler():
        return 434
        # Kälteanlagen

    # Verdampfer
    def IfcEvaporator():
        if self.system is not None:
            if referenceVentilation in self.system or referenceVentilation2 in self.system or referenceVentilation3 in self.system:
                return 430
                # Raumlufttechnische Anlage
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # Wärmetauscher
    def IfcHeatExchanger():
        if self.system is not None:
            if referenceHeating in self.system:
                return 422
                # Wärmeverteilnetze
            elif referenceVentilation in self.system or referenceVentilation2 in self.system or referenceVentilation3 in self.system:
                return 430
                # Raumlufttechnische Anlagen
            elif referenceCooling in self.system:
                return 434
                # Kälteanlagen
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # Befeuchter
    def IfcHumidifier():
        return 430
        # Raumlufttechnische Anlagen

    # Motoranschluss
    def IfcMotorConnection():
        return 400
        # Bauwerk — Technische Anlagen

    # Solargerät
    def IfcSolarDevice():
        ''' TypeEnumeration
            SOLARCOLLECTOR
            SOLARPANEL
            USERDEFINED
            NOTDEFINED
        '''
        if self.enum == "SOLARCOLLECTOR":
            return 421
            # Wärmeerzeugungsanlagen
        elif self.enum == "SOLARPANEL":
            return 442
            # Eigenstromversorgungsanlagen
        else:
            return 400
            # Bauwerk — Technische Anlagen

    # Transformator
    def IfcTransformer():
        return 441
        # Hoch- und Mittelspannungsanlagen

    # Rohrbündel
    def IfcTubeBundle():
        return 400
        # Bauwerk — Technische Anlagen

    # Einbaufertige Anlage
    def IfcUnitaryEquipment():
        ''' TypeЕnumeration
            AIRHANDLER
            AIRCONDITIONINGUNIT
            DEHUMIDIFIER
            SPLITSYSTEM
            ROOFTOPUNIT
            USERDEFINED
            NOTDEFINED
        '''
        if self.enum == "AIRHANDLER":
            return 431
            # Lüftungsanlagen
        elif self.enum == "AIRCONDITIONINGUNIT":
            return 432
            # Teilklimaanlagen
        elif self.enum == "DEHUMIDIFIER":
            return 430
            # Raumlufttechnische Anlagen
        elif self.enum == "ROOFTOPUNIT":
            return 433
            # Klimaanlagen
        else:
            return 400
            # Bauwerk — Technische Anlagen

    # ----- IfcFlowController: ----- #
    # Volumenstromregler
    def IfcAirTerminalBox():
        return 430
        # Raumlufttechnische Anlagen

    # Regelklappe
    def IfcDamper():
        return 430
        # Raumlufttechnische Anlagen

    # Elektrischer Verteilungsregler
    def IfcElectricDistributionBoard():
        return 440
        # Elektrische Anlagen

    # Elektronische Zeitsteuerung
    def IfcElectricTimeControl():
        return 452
        # Zeitdienstanlagen

    # Zähler
    def IfcFlowMeter():
        if self.system is not None:
            if referenceWater in self.system or referenceWater2 in self.system:
                return 412
                # Wasseranlagen
            elif referenceGas in self.system:
                return 413
                # Gasanlagen
            elif referenceHeating in self.system:
                return 422
                # Wärmeverteilnetze
            elif referenceCooling in self.system:
                return 434
                # Kälteanlagen
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # Sicherung
    def IfcProtectiveDevice():
        return 440
        # Elektrische Anlagen

    # Schalter
    def IfcSwitchingDevice():
        return 440
        # Elektrische Anlagen

    # Ventil
    def IfcValve():
        if self.system is not None:
            if referenceWasteWater in self.system:
                return 411
                # Abwasseranlagen
            elif referenceWater in self.system or referenceWater2 in self.system:
                return 412
                # Wasseranlagen
            elif referenceGas in self.system:
                return 413
                # Gasanlagen
            elif referenceHeating in self.system:
                return 422
                # Wärmeverteilnetze
            elif referenceCooling in self.system:
                return 434
                # Kälteanlagen
            elif referenceSprinkler in self.system:
                return 474
                # Feuerlöschanlagen
            elif referenceRainWater in self.system:
                return 369
                # Sonstiges zur KG 360
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # ----- IfcFlowFitting: ----- #
    # Kabelträger Passstück:
    def IfcCableCarrierFitting():
        return 440
        # Elektrische Anlagen

    # Kabelverbinder
    def IfcCableFitting():
        return 440
        # Elektrische Anlagen

    # Kanalverbinder
    def IfcDuctFitting():
        return 430
        # Raumlufttechnische Anlagen

    # Verbindungsdose
    def IfcJunctionBox():
        return 440
        # Elektrische Anlagen

    # Rohrverbinder
    def IfcPipeFitting():
        if self.system is not None:
            if referenceWasteWater in self.system:
                return 411
                # Abwasseranlagen
            elif referenceWater in self.system or referenceWater2 in self.system:
                return 412
                # Wasseranlagen
            elif referenceGas in self.system:
                return 413
                # Gasanlagen
            elif referenceHeating in self.system:
                return 422
                # Wärmeverteilnetze
            elif referenceCooling in self.system:
                return 434
                # Kälteanlagen
            elif referenceSprinkler in self.system:
                return 474
                # Feuerlöschanlagen
            elif referenceRainWater in self.system:
                return 369
                # Sonstiges zur KG 360
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # ----- IfcFlowMovingDevice: ----- #
    # Kompressor:
    def IfcCompressor():
        return 434
        # Kälteanlagen

    # Ventilator
    def IfcFan():
        return 430
        # Raumlufttechnische Anlgen

    # Pumpe
    def IfcPump():
        if self.system is not None:
            if referenceWasteWater in self.system:
                return 411
                # Abwasseranlagen
            elif referenceWater in self.system or referenceWater2 in self.system:
                return 412
                # Wasseranlagen
            elif referenceGas in self.system:
                return 413
                # Gasanlagen
            elif referenceHeating in self.system:
                return 422
                # Wärmeverteilnetze
            elif referenceCooling in self.system:
                return 434
                # Kälteanlagen
            elif referenceSprinkler in self.system:
                return 474
                # Feuerlöschanlagen
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # ----- IfcFlowSegment: ----- #
    def IfcFlowSegment():
        if referenceForGutter in self.name:
            return 369
            # Sonstiges zur KG 360
        else:
            return 400

    # Kabelträgersegment:
    def IfcCableCarrierSegment():
        return 440
        # Elektrische Anlagen

    # Kabelsegment
    def IfcCableSegment():
        return 440
        # Elektrische Anlagen

    # Kanalsegment
    def IfcDuctSegment():
        return 430
        # Raumlufttechnische Anlagen

    # Rohr
    def IfcPipeSegment():
        if self.system is not None:
            if referenceWasteWater in self.system:
                return 411
                # Abwasseranlagen
            elif referenceWater in self.system or referenceWater2 in self.system:
                return 412
                # Wasseranlagen
            elif referenceGas in self.system:
                return 413
                # Gasanlagen
            elif referenceHeating in self.system:
                return 422
                # Wärmeverteilnetze
            elif referenceCooling in self.system:
                return 434
                # Kälteanlagen
            elif referenceSprinkler in self.system:
                return 474
                # Feuerlöschanlagen
            elif referenceRainWater in self.system:
                return 369
                # Sonstiges zur KG 360
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # Tank
    def IfcTank():
        if self.system is not None:
            if referenceWasteWater in self.system:
                return 411
                # Abwasseranlagen
            elif referenceWater in self.system or referenceWater2 in self.system:
                return 412
                # Wasseranlagen
            elif referenceGas in self.system:
                return 413
                # Gasanlagen
            elif referenceHeating in self.system:
                return 422
                # Wärmeverteilnetze
            elif referenceCooling in self.system:
                return 434
                # Kälteanlagen
            elif referenceSprinkler in self.system:
                return 474
                # Feuerlöschanlagen
            elif referenceRainWater in self.system:
                return 412
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # ----- IfcFlowTerminal: ----- #
    # Luftauslass:
    def IfcAirTerminal():
        return 430
        # Raumlufttechnische Anlagen

    # Audiovisuelles Gerät
    def IfcAudioVisualAppliance():
        return 630
        # Informationstechnische Ausstattung

    # Kommunikationgerät
    def IfcCommunicationsAppliance():
        return 451
        # Telekekommunikationsanlagen

    # Elektisches Gerät
    def IfcElectricAppliance():
        ''' TypeEnumeration
            DISHWASHER
            ELECTRICCOOKER
            FREESTANDINGELECTRICHEATER
            FREESTANDINGFAN
            FREESTANDINGWATERHEATER
            FREESTANDINGWATERCOOLER
            FREEZER
            FRIDGE_FREEZER
            HANDDRYER
            KITCHENMACHINE
            MICROWAVE
            PHOTOCOPIER
            REFRIGERATOR
            TUMBLEDRYER
            VENDINGMACHINE
            WASHINGMACHINE
            USERDEFINED
            NOTDEFINED
        '''
        if self.enum == ("DISHWASHER"):
            self.KG = 471
            # Küchentechnische Anlagen
        elif self.enum == ("ELECTRICCOOKER"):
            self.KG = 471
            # Küchentechnische Anlagen
        elif self.enum == ("FREESTANDINGELECTRICHEATER"):
            self.KG = 421
            # Wärmeerzeugungsanlagen
        elif self.enum == ("FREESTANDINGFAN"):
            self.KG = 431
            # Lüftungsanlagen
        elif self.enum == ("FREESTANDINGWATERHEATER"):
            self.KG = 421
            # Wärmeerzeugungsanlagen
        elif self.enum == ("FREESTANDINGWATERCOOLER"):
            self.KG = 434
            # Kälteanlagen
        elif self.enum == ("FREEZER"):
            self.KG = 471
            # Küchentechnische Anlagen
        elif self.enum == ("FRIDGE_FREEZER"):
            self.KG = 471
            # Küchentechnische Anlagen
        elif self.enum == ("HANDDRYER"):
            self.KG = 412
            # Wasseranlagen
        elif self.enum == ("KITCHENMACHINE"):
            self.KG = 471
            # Küchentechnische Anlagen
        elif self.enum == ("MICROWAVE"):
            self.KG = 471
            # Küchentechnische Anlagen
        elif self.enum == ("PHOTOCOPIER"):
            self.KG = 630
            # Informationstechnische Ausstattung
        elif self.enum == ("REFRIGERATOR"):
            self.KG = 471
        elif self.enum == ("TUMBLEDRYER"):
            self.KG = 412
            # Wasseranlagen
        elif self.enum == ("VENDINGMACHINE"):
            self.KG = 471
            # Küchentechnische Anlagen
        elif self.enum == ("WASHINGMACHINE"):
            self.KG = 412
            # Wasseranlagen
        else:
            return 400
            # Bauwerk — Technische Anlagen

    # Feuerlöscheinrichtung
    def IfcFireSuppressionTerminal():
        return 474
        # Feuerlöschanlagen

    # Lampe/Leuchtmittel
    def IfcLamp():
        return 445
        # Beleuchtungsanlagen

    # Leuchte
    def IfcLightFixture():
        return 445
        # Beleuchtungsmittel

    # Medizinisches Gerät
    def IfcMedicalDevice():
        return 620
        # Besondere Ausstattung

    # Dose/Steckdose
    def IfcOutlet():
        return 440
        # Elektrische Anlagen

    # Sanitäreinrichtung
    def IfcSanitaryTerminal():
        if self.system is not None:
            if referenceWasteWater in self.system:
                return 411
                # Abwasseranlagen
            elif referenceWater in self.system or referenceWater2 in self.system:
                return 412
                # Wasseranlagen
        else:
            return 410
            # Abwasser-, Wasser-, Gasanlagen

    # Heizkörper
    def IfcSpaceHeater():
        return 423
        # Raumheizflächen

    # Rohrabdeckung
    def IfcStackTerminal():
        return 412
        # Wasseranlagen

    # Ablauf / Abscheider
    def IfcWasteTerminal():
        if self.system is not None:
            if referenceWasteWater in self.system:
                return 411
                # Abwasseranlagen
            elif referenceWater in self.system or referenceWater2 in self.system:
                return 412
                # Wasseranlagen
            elif referenceRainWater in self.system:
                return 369
                # Sonstiges zur KG 360
            else:
                return 400
                # Bauwerk — Technische Anlagen
        else:
            return 000

    # ----- IfcFlowTerminal: ----- #
    # Endgerät
    def IfcFlowTerminal():
        return 412
        # Wasseranlagen

    # Kanalschalldämpfer:
    def IfcDuctSilencer():
        return 430
        # Raumlufttechnische Anlagen

    # Filter
    def IfcFilter():
        ''' TypeEnumeration
            AIRPARTICLEFILTER
            COMPRESSEDAIRFILTER
            ODORFILTER
            OILFILTER
            STRAINER
            WATERFILTER
            USERDEFINED
            NOTDEFINED
        '''
        if self.enum == "AIRPARTICLEFILTER":
            return 430
            # Raumlufttechnische Anlagen
        elif self.enum == "COMPRESSEDAIRFILTER":
            return 430
            # Raumlufttechnische Anlagen
        elif self.enum == "ODORFILTER":
            return 430
            # Raumlufttechnische Anlagen
        elif self.enum == "OILFILTER":
            return 400
            # Bauwerk — Technische Anlagen
        elif self.enum == "STRAINER":
            if self.system is not None:
                if referenceWasteWater in self.system:
                    return 411
                    # Abwasseranlagen
                elif referenceWater in self.system or referenceWater2 in self.system:
                    return 412
                    # Wasseranlagen
                elif referenceGas in self.system:
                    return 413
                    # Gasanlagen
                elif referenceRainWater in self.system:
                    return 369
                    # Sonstiges zur KG 360
                else:
                    return 410
                # Abwasser-, Wasser-, Gasanlagen
            else:
                return 000
        elif self.enum == "WATERFILTER":
            return 412
            # Wasseranlagen
        else:
            return 400
            # Bauwerk — Technische Anlagen

    # Abscheider
    def IfcInterceptor():
        return 400
        # Bauwerk — Technische Anlagen

    # ----- IfcFurnishingElement: ----- #
    def IfcFurniture():
        return 610
        # Allgemeine Ausstattung

    def IfcSystemFurnitureElement():
        return 610
        # Allgemeine Ausstattung

    switcher = {"IfcBeam": IfcBeam,
                "IfcBuildingElementProxy": IfcBuildingElementProxy,
                "IfcChimney": IfcChimney,
                "IfcColumn": IfcColumn,
                "IfcCovering": IfcCovering,
                "IfcCurtainWall": IfcCurtainWall,
                "IfcDoor": IfcDoor,
                "IfcFooting": IfcFooting,
                "IfcMember": IfcMember,
                "IfcPile": IfcPile,
                "IfcPlate": IfcPlate,
                "IfcRailing": IfcRailing,
                "IfcRamp": IfcRamp,
                "IfcRampFlight": IfcRampFlight,
                "IfcRoof": IfcRoof,
                "IfcShadingDevice": IfcShadingDevice,
                "IfcSlab": IfcSlab,
                "IfcStair": IfcStair,
                "IfcStairFlight": IfcStairFlight,
                "IfcWall": IfcWall,
                "IfcWallStandardCase": IfcWall,
                "IfcWallElementedCase": IfcWall,
                "IfcWindow": IfcWindow,
                "IfcActuator": IfcActuator,
                "IfcAlarm": IfcAlarm,
                "IfcController": IfcController,
                "IfcFlowInstrument": IfcFlowInstrument,
                "IfcProtectiveDeviceTrippingUnit": IfcProtectiveDeviceTrippingUnit,
                "IfcSensor": IfcSensor,
                "IfcUnitaryControlElement": IfcUnitaryControlElement,
                "IfcDistributionChamberElement": IfcDistributionChamberElement,
                "IfcAirToAirHeatRecovery": IfcAirToAirHeatRecovery,
                "IfcBoiler": IfcBoiler,
                "IfcBurner": IfcBurner,
                "IfcChiller": IfcChiller,
                "IfcCoil": IfcCoil,
                "IfcCondenser": IfcCondenser,
                "IfcCooledBeam": IfcCooledBeam,
                "IfcCoolingTower": IfcCoolingTower,
                "IfcElectricGenerator": IfcElectricGenerator,
                "IfcElectricMotor": IfcElectricMotor,
                "IfcMotor": IfcEngine,
                "IfcEvaporativeCooler": IfcEvaporativeCooler,
                "IfcEvaporator": IfcEvaporator,
                "IfcHeatExchanger": IfcHeatExchanger,
                "IfcHumidifier": IfcHumidifier,
                "IfcMotorConnection": IfcMotorConnection,
                "IfcSolarDevice": IfcSolarDevice,
                "IfcTransformer": IfcTransformer,
                "IfcTubeBundle": IfcTubeBundle,
                "IfcUnitaryEquipment": IfcUnitaryEquipment,
                "IfcAirTerminalBox": IfcAirTerminalBox,
                "IfcDamper": IfcDamper,
                "IfcElectricDistributionBoard": IfcElectricDistributionBoard,
                "IfcElectricTimeControl": IfcElectricTimeControl,
                "IfcFlowMeter": IfcFlowMeter,
                "IfcProtectiveDevice": IfcProtectiveDevice,
                "IfcSwitchingDevice": IfcSwitchingDevice,
                "IfcValve": IfcValve,
                "IfcCableCarrierFitting": IfcCableCarrierFitting,
                "IfcCableFitting": IfcCableFitting,
                "IfcDuctFitting": IfcDuctFitting,
                "IfcJunctionBox": IfcJunctionBox,
                "IfcPipeFitting": IfcPipeFitting,
                "IfcCompressor": IfcCompressor,
                "IfcFan": IfcFan,
                "IfcPump": IfcPump,
                "IfcFlowSegment": IfcFlowSegment,
                "IfcCableCarrierSegment": IfcCableCarrierSegment,
                "IfcCableSegment": IfcCableSegment,
                "IfcDuctSegment": IfcDuctSegment,
                "IfcPipeSegment": IfcPipeSegment,
                "IfcTank": IfcTank,
                "IfcAirTerminal": IfcAirTerminal,
                "IfcAudioVisualAppliance": IfcAudioVisualAppliance,
                "IfcCommunicationsAppliance": IfcCommunicationsAppliance,
                "IfcElectricAppliance": IfcElectricAppliance,
                "IfcFireSuppressionTerminal": IfcFireSuppressionTerminal,
                "IfcLamp": IfcLamp,
                "IfcLightFixture": IfcLightFixture,
                "IfcMedicalDevice": IfcMedicalDevice,
                "IfcOutlet": IfcOutlet,
                "IfcSanitaryTerminal": IfcSanitaryTerminal,
                "IfcSpaceHeater": IfcSpaceHeater,
                "IfcStackTerminal": IfcStackTerminal,
                "IfcWasteTerminal": IfcWasteTerminal,
                "IfcDuctSilencer": IfcDuctSilencer,
                "IfcFilter": IfcFilter,
                "IfcInterceptor": IfcInterceptor,
                "IfcFurniture": IfcFurniture,
                "IfcSystemFurnitureElement": IfcSystemFurnitureElement,
                "IfcFlowTerminal": IfcFlowTerminal}

    func = switcher.get(self.product.is_a(), lambda: None)
    return func()


def getKGname(self):
    def KG300():
        return "Bauwerk Baukonstruktionen"

    def KG310():
        return "Baugrube/Erdbau"

    def KG311():
        return "Herstellung"

    def KG312():
        return "Umschließung"

    def KG313():
        return "Wasserhaltung"

    def KG314():
        return "Vortrieb"

    def KG319():
        return "Sonstiges zur KG 310: Baugrube/Erdbau"

    def KG320():
        return "Gründung, Unterbau"

    def KG321():
        return "Baugrundverbesserung"

    def KG322():
        return "Flachgründungen und Bodenplatten"

    def KG323():
        return "Tiefgründungen"

    def KG324():
        return "Gründungsbeläge"

    def KG325():
        return "Abdichtungen und Bekleidungen"

    def KG326():
        return "Dränagen"

    def KG329():
        return "Sonstiges zur KG 320: Gründung, Unterbau"

    def KG330():
        return "Außenwände/Vertikale Baukonstruktionen, außen"

    def KG331():
        return "Tragende Außenwände"

    def KG332():
        return "Nichttragende Außenwände"

    def KG333():
        return "Außenstützen"

    def KG334():
        return "Außenwandöffnungen"

    def KG335():
        return "Außenwandbekleidungen, außen"

    def KG336():
        return "Außenwandbekleidungen, innen"

    def KG337():
        return "Elementierte Außenwandkonstruktionen"

    def KG338():
        return "Lichtschutz zur KG 330: Außenwände/Vertikale Baukonstruktionen, außen"

    def KG339():
        return "Sonstiges zur KG 330: Außenwände/Vertikale Baukonstruktionen, außen"

    def KG340():
        return "Innenwände/Vertikale Baukonstruktionen, innen"

    def KG341():
        return "Tragende Innenwände"

    def KG342():
        return "Nichttragende Innenwände"

    def KG343():
        return "Innenstützen"

    def KG344():
        return "Innenwandöffnungen"

    def KG345():
        return "Innenwandbekleidungen"

    def KG346():
        return "Elementierte Innenwandkonstruktionen"

    def KG347():
        return "Lichtschutz zur KG 340: Innenwände/Vertikale Baukonstruktionen, innen"

    def KG349():
        return "Sonstiges zur KG 340: Innenwände/Vertikale Baukonstruktionen, innen"

    def KG350():
        return "Decken/Horizontale Baukonstruktionen"

    def KG351():
        return "Deckenkonstruktionen"

    def KG352():
        return "Deckenöffnungen"

    def KG353():
        return "Deckenbeläge"

    def KG354():
        return "Deckenbekleidungen"

    def KG355():
        return "Elementierte Deckenkonstruktionen"

    def KG359():
        return "Sonstiges zur KG 350: Decken/Horizontale Baukonstruktionen"

    def KG360():
        return "Dächer"

    def KG361():
        return "Dachkonstruktionen"

    def KG362():
        return "Dachöffnungen"

    def KG363():
        return "Dachbeläge"

    def KG364():
        return "Dachbekleidungen"

    def KG365():
        return "Elementierte Dachkonstruktionen"

    def KG366():
        return "Lichtschutz zur KG 360: Dächer"

    def KG369():
        return "Sonstiges zur KG 360: Dächer"

    def KG400():
        return "Bauwerk — Technische Anlagen"

    def KG410():
        return "Abwasser-, Wasser-, Gasanlagen"

    def KG411():
        return "Abwasseranlagen"

    def KG412():
        return "Wasseranlagen"

    def KG413():
        return "Gasanlagen"

    def KG419():
        return "Sonstiges zur KG 410: Abwasser-, Wasser-, Gasanlagen"

    def KG420():
        return "Wärmeversorgungsanlagen"

    def KG421():
        return "Wärmeerzeugungsanlagen"

    def KG422():
        return "Wärmeverteilnetze"

    def KG423():
        return "Raumheizflächen"

    def KG424():
        return "Verkehrsheizflächen"

    def KG429():
        return "Sonstiges zur KG 420: Wärmeversorgungsanlagen"

    def KG430():
        return "Raumlufttechnische Anlagen"

    def KG431():
        return "Lüftungsanlagen"

    def KG432():
        return "Teilklimaanlagen"

    def KG433():
        return "Klimaanlagen"

    def KG434():
        return "Kälteanlagen"

    def KG439():
        return "Sonstiges zur KG 430: Raumlufttechnische Anlagen"

    def KG440():
        return "Elektrische Anlagen"

    def KG441():
        return "Hoch- und Mittelspannungsanlagen"

    def KG442():
        return "Eigenstromversorgungsanlagen"

    def KG443():
        return "Niederspannungsschaltanlagen"

    def KG444():
        return "Niederspannungsinstallationsanlagen"

    def KG445():
        return "Beleuchtungsanlagen"

    def KG446():
        return "Blitzschutz- und Erdungsanlagen"

    def KG447():
        return "Fahrleitungssysteme"

    def KG449():
        return "Sonstiges zur KG 440: Elektrische Anlagen"

    def KG450():
        return "Kommunikations-, sicherheits- und informationstechnische Anlagen"

    def KG451():
        return "Telekommunikationsanlagen"

    def KG452():
        return "Such- und Signalanlagen"

    def KG453():
        return "Zeitdienstanlagen"

    def KG454():
        return "Elektroakustische Anlagen"

    def KG455():
        return "Audiovisuelle Medien- und Antennenanlagen"

    def KG456():
        return "Gefahrenmelde- und Alarmanlagen"

    def KG457():
        return "Datenübertragungsnetze"

    def KG458():
        return "Verkehrsbeeinflussungsanlagen"

    def KG459():
        return "Sonstiges zur KG 450: Kommunikations-, sicherheits- und informationstechnische Anlagen"

    def KG460():
        return "Förderanlagen"

    def KG461():
        return "Aufzugsanlagen"

    def KG462():
        return "Fahrtreppen, Fahrsteige"

    def KG463():
        return "Befahranlagen"

    def KG464():
        return "Transportanlagen"

    def KG465():
        return "Krananlagen"

    def KG466():
        return "Hydraulikanlagen"

    def KG469():
        return "Sonstiges zur KG 460: Förderanlagen"

    def KG470():
        return "Nutzungsspezifische und verfahrenstechnische Anlagen"

    def KG471():
        return "Küchentechnische Anlagen"

    def KG472():
        return "Wäscherei-, Reinigungsund badetechnische Anlagen"

    def KG473():
        return "Medienversorgungsanlagen, Medizin- und labortechnische Anlagen"

    def KG474():
        return "Feuerlöschanlagen"

    def KG475():
        return "Prozesswärme-, kälte- und -luftanlagen"

    def KG476():
        return "Weitere nutzungsspezifische Anlagen"

    def KG477():
        return "Verfahrenstechnische Anlagen, Wasser, Abwasser und Gase"

    def KG478():
        return "Verfahrenstechnische Anlagen, Feststoffe, Wertstoffe und Abfälle"

    def KG479():
        return "Sonstiges zur KG 470: Nutzungsspezifische und verfahrenstechnische Anlagen"

    def KG480():
        return "Gebäude- und Anlagenautomation"

    def KG481():
        return "Automationseinrichtungen"

    def KG482():
        return "Schaltschränke, Automationsschwerpunkte"

    def KG483():
        return "Automationsmanagement"

    def KG484():
        return "Kabel, Leitungen und Verlegesysteme"

    def KG485():
        return "Datenübertragungsnetze"

    def KG489():
        return "Sonstiges zur KG 480: Gebäude- und Anlagenautomation"

    def KG600():
        return "Ausstattung und Kunstwerke"

    def KG610():
        return "Allgemeine Ausstattung"

    def KG620():
        return "Besondere Ausstattung"

    def KG630():
        return "Informationstechnische Ausstattung"

    def KG640():
        return "Künstlerische Ausstattung"

    def KG690():
        return "Sonstige Ausstattung"

    def KGunklar():
        return "Kostengruppe kann nicht ermittelt werden. Grund dafür ist Mangel an Information."

    switcher = {
        300: KG300,
        310: KG310,
        311: KG311,
        312: KG312,
        313: KG313,
        314: KG314,
        319: KG319,
        320: KG320,
        321: KG321,
        322: KG322,
        323: KG323,
        324: KG324,
        325: KG325,
        326: KG326,
        329: KG329,
        330: KG330,
        331: KG331,
        332: KG332,
        333: KG333,
        334: KG334,
        335: KG335,
        336: KG336,
        337: KG337,
        338: KG338,
        339: KG339,
        340: KG340,
        341: KG341,
        342: KG342,
        343: KG343,
        344: KG344,
        345: KG345,
        346: KG346,
        347: KG347,
        349: KG349,
        350: KG350,
        351: KG351,
        352: KG352,
        353: KG353,
        354: KG354,
        355: KG355,
        359: KG359,
        360: KG360,
        361: KG361,
        362: KG362,
        363: KG363,
        364: KG364,
        365: KG365,
        366: KG366,
        369: KG369,
        400: KG400,
        410: KG410,
        411: KG411,
        412: KG412,
        413: KG413,
        419: KG419,
        420: KG420,
        421: KG421,
        422: KG422,
        423: KG423,
        424: KG424,
        429: KG429,
        430: KG430,
        431: KG431,
        432: KG432,
        433: KG433,
        434: KG434,
        439: KG439,
        440: KG440,
        441: KG441,
        442: KG442,
        443: KG443,
        444: KG444,
        445: KG445,
        446: KG446,
        447: KG447,
        449: KG449,
        450: KG450,
        451: KG451,
        452: KG452,
        453: KG453,
        454: KG454,
        455: KG455,
        456: KG456,
        457: KG457,
        458: KG458,
        459: KG459,
        460: KG460,
        461: KG461,
        462: KG462,
        463: KG464,
        464: KG464,
        465: KG465,
        466: KG466,
        469: KG469,
        470: KG470,
        471: KG471,
        472: KG472,
        473: KG473,
        474: KG474,
        475: KG475,
        476: KG476,
        477: KG477,
        478: KG478,
        479: KG479,
        480: KG480,
        481: KG481,
        482: KG482,
        483: KG483,
        484: KG484,
        485: KG485,
        489: KG489,
        600: KG600,
        610: KG610,
        620: KG620,
        630: KG630,
        640: KG640,
        690: KG690,
        000: KGunklar}

    func = switcher.get(self.KG, lambda: None)
    return func()
