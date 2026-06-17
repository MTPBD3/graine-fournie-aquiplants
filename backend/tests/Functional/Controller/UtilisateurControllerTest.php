<?php

namespace App\Tests\Functional\Controller;

class UtilisateurControllerTest extends ApiTestCase
{
    private const NEW_USER_EMAIL = 'tc-new-user@test.aquiplants';

    // ── /mon-profil ───────────────────────────────────────────────────────────

    public function testMonProfilSansTokenRetourne401(): void
    {
        $this->browser->request('PATCH', '/api/utilisateurs/mon-profil', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMonProfilMotDePasseVideRetourne400(): void
    {
        $this->browser->request(
            'PATCH', '/api/utilisateurs/mon-profil', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['motdepasse' => ''])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testMonProfilMotDePasseTropCourtRetourne422(): void
    {
        $this->browser->request(
            'PATCH', '/api/utilisateurs/mon-profil', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['motdepasse' => 'court'])
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testMonProfilMotDePasseSansMajRetourne422(): void
    {
        $this->browser->request(
            'PATCH', '/api/utilisateurs/mon-profil', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['motdepasse' => 'sansmajouscule1234'])
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testMonProfilMotDePasseSansChiffreRetourne422(): void
    {
        $this->browser->request(
            'PATCH', '/api/utilisateurs/mon-profil', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['motdepasse' => 'SansChiffreMdp'])
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testMonProfilValideRetourne200(): void
    {
        $this->browser->request(
            'PATCH', '/api/utilisateurs/mon-profil', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['motdepasse' => 'NouveauMdp1234'])
        );
        $this->assertResponseIsSuccessful();
        // Rétablir le mot de passe d'origine pour les autres tests
        $this->browser->request(
            'PATCH', '/api/utilisateurs/mon-profil', [], [],
            $this->jsonHeaders($this->loginAs(self::EMPLOYE_EMAIL, 'NouveauMdp1234')),
            json_encode(['motdepasse' => self::EMPLOYE_PASSWORD])
        );
    }

    // ── GET index ─────────────────────────────────────────────────────────────

    public function testIndexSansTokenRetourne401(): void
    {
        $this->browser->request('GET', '/api/utilisateurs');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testIndexAvecTokenEmployeRetourne403(): void
    {
        $this->browser->request('GET', '/api/utilisateurs', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexAvecTokenAdminRetourne200(): void
    {
        $this->browser->request('GET', '/api/utilisateurs', [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    // ── GET show ─────────────────────────────────────────────────────────────

    public function testShowAvecTokenEmployeRetourne403(): void
    {
        $this->browser->request('GET', '/api/utilisateurs/1', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testShowIntrouvableRetourne404(): void
    {
        $this->browser->request('GET', '/api/utilisateurs/999999', [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowAvecTokenAdminRetourne200(): void
    {
        // Crée un utilisateur puis le récupère via show() → couvre le chemin succès
        $email = 'tc-show-' . uniqid() . '@test.aquiplants';
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['nom' => 'ShowUser', 'prenom' => 'TC', 'email' => $email, 'motdepasse' => 'ValidPwd1234', 'role' => 'ROLE_EMPLOYE'])
        );
        $created = json_decode($this->browser->getResponse()->getContent(), true);
        $id = $created['id'];
        $this->scheduleDelete('utilisateur', 'id_utilisateur', $id);

        $this->browser->request('GET', "/api/utilisateurs/$id", [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertSame($email, $data['email']);
    }

    // ── POST create ───────────────────────────────────────────────────────────

    public function testCreateAvecTokenEmployeRetourne403(): void
    {
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['email' => 'x@x.fr', 'motdepasse' => 'Test1234567'])
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateEmailVideRetourne400(): void
    {
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['email' => '', 'motdepasse' => 'Test1234567'])
        );
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateMotDePasseFaibleRetourne422(): void
    {
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['email' => 'weak@test.fr', 'motdepasse' => 'faible'])
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateRoleInvalideRetourne422(): void
    {
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['email' => 'role@test.fr', 'motdepasse' => 'ValidPwd1234', 'role' => 'ROLE_SUPER_ADMIN'])
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateRetourne201(): void
    {
        $email = 'tc-new-' . uniqid() . '@test.aquiplants';
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode([
                'nom'       => 'Nouveau',
                'prenom'    => 'User',
                'email'     => $email,
                'motdepasse'=> 'ValidPwd1234',
                'role'      => 'ROLE_EMPLOYE',
            ])
        );
        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->scheduleDelete('utilisateur', 'id_utilisateur', $data['id']);
    }

    // ── PUT update ────────────────────────────────────────────────────────────

    public function testUpdateAvecTokenEmployeRetourne403(): void
    {
        $this->browser->request(
            'PUT', '/api/utilisateurs/1', [], [],
            $this->jsonHeaders($this->employeToken),
            json_encode(['nom' => 'X'])
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateIntrouvableRetourne404(): void
    {
        $this->browser->request(
            'PUT', '/api/utilisateurs/999999', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['nom' => 'X'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateRoleInvalideRetourne422(): void
    {
        // Créer un utilisateur temporaire
        $email = 'tc-upd-' . uniqid() . '@test.aquiplants';
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['nom' => 'Tmp', 'prenom' => 'U', 'email' => $email, 'motdepasse' => 'ValidPwd1234', 'role' => 'ROLE_EMPLOYE'])
        );
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $id   = $data['id'];
        $this->scheduleDelete('utilisateur', 'id_utilisateur', $id);

        $this->browser->request(
            'PUT', "/api/utilisateurs/$id", [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['role' => 'ROLE_INVALIDE'])
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateRetourne200(): void
    {
        $email = 'tc-upd2-' . uniqid() . '@test.aquiplants';
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['nom' => 'Tmp2', 'prenom' => 'U', 'email' => $email, 'motdepasse' => 'ValidPwd1234', 'role' => 'ROLE_EMPLOYE'])
        );
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $id   = $data['id'];
        $this->scheduleDelete('utilisateur', 'id_utilisateur', $id);

        $this->browser->request(
            'PUT', "/api/utilisateurs/$id", [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['nom' => 'NomMisAJour'])
        );
        $this->assertResponseIsSuccessful();
    }

    public function testUpdateAvecMotDePasseRetourne200(): void
    {
        // Couvre la branche if (!empty($newPassword)) dans update()
        $email = 'tc-updpwd-' . uniqid() . '@test.aquiplants';
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['nom' => 'TmpPwd', 'prenom' => 'U', 'email' => $email, 'motdepasse' => 'ValidPwd1234', 'role' => 'ROLE_EMPLOYE'])
        );
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $id   = $data['id'];
        $this->scheduleDelete('utilisateur', 'id_utilisateur', $id);

        $this->browser->request(
            'PUT', "/api/utilisateurs/$id", [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['motdepasse' => 'NouveauPwd9876'])
        );
        $this->assertResponseIsSuccessful();
    }

    public function testUpdateAvecMotDePasseInvalidRetourne422(): void
    {
        // Couvre return $this->json(['message' => $error], 422) dans update() quand mdp invalide
        $email = 'tc-updpwdbad-' . uniqid() . '@test.aquiplants';
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['nom' => 'TmpBadPwd', 'prenom' => 'U', 'email' => $email, 'motdepasse' => 'ValidPwd1234', 'role' => 'ROLE_EMPLOYE'])
        );
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $id   = $data['id'];
        $this->scheduleDelete('utilisateur', 'id_utilisateur', $id);

        $this->browser->request(
            'PUT', "/api/utilisateurs/$id", [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['motdepasse' => 'court'])
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testUpdateAvecRoleValideRetourne200(): void
    {
        // Couvre $u->setRole($data['role']) dans update() — la branche role invalide retourne 422 avant cette ligne
        $email = 'tc-updrole-' . uniqid() . '@test.aquiplants';
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['nom' => 'TmpRole', 'prenom' => 'U', 'email' => $email, 'motdepasse' => 'ValidPwd1234', 'role' => 'ROLE_EMPLOYE'])
        );
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $id   = $data['id'];
        $this->scheduleDelete('utilisateur', 'id_utilisateur', $id);

        $this->browser->request(
            'PUT', "/api/utilisateurs/$id", [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['role' => 'ROLE_ADMIN'])
        );
        $this->assertResponseIsSuccessful();
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    public function testDeleteAvecTokenEmployeRetourne403(): void
    {
        $this->browser->request('DELETE', '/api/utilisateurs/1', [], [], $this->jsonHeaders($this->employeToken));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteIntrouvableRetourne404(): void
    {
        $this->browser->request('DELETE', '/api/utilisateurs/999999', [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteRetourne204(): void
    {
        $email = 'tc-del-' . uniqid() . '@test.aquiplants';
        $this->browser->request(
            'POST', '/api/utilisateurs', [], [],
            $this->jsonHeaders($this->adminToken),
            json_encode(['nom' => 'TmpDel', 'prenom' => 'U', 'email' => $email, 'motdepasse' => 'ValidPwd1234', 'role' => 'ROLE_EMPLOYE'])
        );
        $data = json_decode($this->browser->getResponse()->getContent(), true);
        $id   = $data['id'];

        $this->browser->request('DELETE', "/api/utilisateurs/$id", [], [], $this->jsonHeaders($this->adminToken));
        $this->assertResponseStatusCodeSame(204);
    }
}
