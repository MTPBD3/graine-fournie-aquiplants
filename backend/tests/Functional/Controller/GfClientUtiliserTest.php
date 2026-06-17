<?php

namespace App\Tests\Functional\Controller;

class GfClientUtiliserTest extends ApiTestCase
{
    /**
     * Couvre le format multi-sachets (utilisations[]) de l'action utiliser.
     */
    public function testUtiliserFormatMultiSachetsRetourne200(): void
    {
        $idEspece = $this->makeEspece('E Multi Util');
        $idPlant  = $this->makePlant('P Multi Util', $idEspece);
        $idClient = $this->makeClient('ClientMultiUtil', 'P');
        $idGf1    = $this->makeGfClient($idClient, $idPlant, 'LOT-MULTI1-' . uniqid(), 100, 10);
        $idGf2    = $this->makeGfClient($idClient, $idPlant, 'LOT-MULTI2-' . uniqid(), 100, 10);
        $idUv     = $this->makeUv($idEspece, 'UV Multi Util');

        $this->browser->request(
            'POST', "/api/gf-clients/$idGf1/utiliser", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode([
                'idUv'         => $idUv,
                'utilisations' => [
                    ['idGfClient' => $idGf1, 'quantite' => 5],
                    ['idGfClient' => $idGf2, 'quantite' => 3],
                ],
            ])
        );
        $this->assertResponseIsSuccessful();

        $this->deleteGfHistoClientByGfClient($idGf1);
        $this->deleteGfHistoClientByGfClient($idGf2);
    }

    /**
     * Couvre le chemin où quantiteUtilisee invalide retourne 400.
     */
    public function testUtiliserQuantiteInvalideRetourne400(): void
    {
        $idEspece = $this->makeEspece('E Qte Invalide');
        $idPlant  = $this->makePlant('P Qte Invalide', $idEspece);
        $idClient = $this->makeClient('ClientQteInvalide', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-QTE-INVALIDE-' . uniqid(), 100, 10);
        $idUv     = $this->makeUv($idEspece, 'UV Qte Invalide');

        $this->browser->request(
            'POST', "/api/gf-clients/$idGf/utiliser", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idUv' => $idUv, 'quantiteUtilisee' => 0])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * Couvre le chemin où nbGraineParMotte est forcé.
     */
    public function testUtiliserAvecNbGraineForceRetourne200(): void
    {
        $idEspece = $this->makeEspece('E NbGraine Force');
        $idPlant  = $this->makePlant('P NbGraine Force', $idEspece);
        $idClient = $this->makeClient('ClientNbGraineForcee', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-NBGRAINE-' . uniqid(), 100, 10);
        $idUv     = $this->makeUv($idEspece, 'UV NbGraine Force');

        $this->browser->request(
            'POST', "/api/gf-clients/$idGf/utiliser", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idUv' => $idUv, 'quantiteUtilisee' => 10, 'nbGraineParMotte' => 8])
        );
        $this->assertResponseIsSuccessful();

        $this->deleteGfHistoClientByGfClient($idGf);
    }

    /**
     * Couvre le chemin où le sachet tombe à 0 (libération emplacement).
     */
    public function testUtiliserJusquAZeroAvecEmplacementRetourne200(): void
    {
        $idEspece = $this->makeEspece('E Zero Util');
        $idPlant  = $this->makePlant('P Zero Util', $idEspece);
        $idClient = $this->makeClient('ClientZeroUtil', 'P');
        $idGf     = $this->makeGfClient($idClient, $idPlant, 'LOT-ZERO-' . uniqid(), 5, 0);
        $idUv     = $this->makeUv($idEspece, 'UV Zero Util');

        // Assigner d'abord un emplacement
        $this->browser->request(
            'POST', '/api/emplacements/assigner', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idGfClient' => $idGf, 'lettreEtagere' => 'D', 'numeroEtage' => 3])
        );
        $data   = json_decode($this->browser->getResponse()->getContent(), true);
        $idEmpl = $data['id'] ?? 0;
        $this->scheduleDelete('emplacement', 'id_emplacement', $idEmpl);

        // Utiliser toute la quantité → sachet à 0 → emplacement libéré
        $this->browser->request(
            'POST', "/api/gf-clients/$idGf/utiliser", [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['idUv' => $idUv, 'quantiteUtilisee' => 5])
        );
        $this->assertResponseIsSuccessful();

        $this->deleteGfHistoClientByGfClient($idGf);
    }
}
