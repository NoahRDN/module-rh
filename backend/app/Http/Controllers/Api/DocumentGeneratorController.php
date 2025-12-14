<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Models\Contrat;
use App\Models\DemandeConge;
use App\Models\Formation;
use App\Services\DocumentGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentGeneratorController extends Controller
{
    private DocumentGeneratorService $documentService;

    public function __construct(DocumentGeneratorService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Liste des types de documents disponibles
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'types' => $this->documentService->getTypesDocuments(),
        ]);
    }

    /**
     * Générer un document
     */
    public function generer(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'employe_id' => 'required|exists:employes,id',
            'params' => 'nullable|array',
        ]);

        $employe = Employe::findOrFail($request->employe_id);
        $type = $request->type;
        $params = $request->params ?? [];

        $result = $this->documentService->genererDocument($type, $employe, $params);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Générer une attestation de travail
     */
    public function attestationTravail(Request $request, $employeId): JsonResponse
    {
        $employe = Employe::findOrFail($employeId);
        $result = $this->documentService->genererAttestationTravail($employe);

        return response()->json($result);
    }

    /**
     * Générer un certificat de travail
     */
    public function certificatTravail(Request $request, $employeId): JsonResponse
    {
        $request->validate([
            'date_fin' => 'nullable|date',
        ]);

        $employe = Employe::findOrFail($employeId);
        $dateFin = $request->date_fin;
        
        $result = $this->documentService->genererCertificatTravail($employe, $dateFin);

        return response()->json($result);
    }

    /**
     * Générer une attestation de salaire
     */
    public function attestationSalaire(Request $request, $employeId): JsonResponse
    {
        $request->validate([
            'mois_count' => 'nullable|integer|min:1|max:12',
        ]);

        $employe = Employe::findOrFail($employeId);
        $moisCount = $request->mois_count ?? 3;
        
        $result = $this->documentService->genererAttestationSalaire($employe, $moisCount);

        return response()->json($result);
    }

    /**
     * Générer une attestation de congé
     */
    public function attestationConge(Request $request, $demandeId): JsonResponse
    {
        $demande = DemandeConge::with('employe')->findOrFail($demandeId);
        
        if ($demande->statut !== 'approuvee') {
            return response()->json([
                'success' => false,
                'error' => 'La demande de congé doit être approuvée pour générer une attestation',
            ], 400);
        }

        $result = $this->documentService->genererAttestationConge($demande);

        return response()->json($result);
    }

    /**
     * Générer une lettre de recommandation
     */
    public function lettreRecommandation(Request $request, $employeId): JsonResponse
    {
        $request->validate([
            'destinataire' => 'nullable|string|max:255',
        ]);

        $employe = Employe::findOrFail($employeId);
        $options = [
            'destinataire' => $request->destinataire ?? 'À qui de droit',
        ];
        
        $result = $this->documentService->genererLettreRecommandation($employe, $options);

        return response()->json($result);
    }

    /**
     * Générer un contrat de travail
     */
    public function contratTravail(Request $request, $contratId): JsonResponse
    {
        $contrat = Contrat::with('employe')->findOrFail($contratId);
        $result = $this->documentService->genererContrat($contrat);

        return response()->json($result);
    }

    /**
     * Générer un avenant au contrat
     */
    public function avenantContrat(Request $request, $contratId): JsonResponse
    {
        $request->validate([
            'modifications' => 'required|array',
            'modifications.motif' => 'required|string',
            'modifications.date_effet' => 'nullable|date',
        ]);

        $contrat = Contrat::with('employe')->findOrFail($contratId);
        $result = $this->documentService->genererAvenant($contrat, $request->modifications);

        return response()->json($result);
    }

    /**
     * Générer une attestation de formation
     */
    public function attestationFormation(Request $request, $employeId, $formationId): JsonResponse
    {
        $employe = Employe::findOrFail($employeId);
        $formation = Formation::findOrFail($formationId);
        
        // Vérifier que l'employé a participé à cette formation
        $inscription = $employe->formations()->where('formation_id', $formationId)->first();
        
        if (!$inscription) {
            return response()->json([
                'success' => false,
                'error' => 'L\'employé n\'a pas participé à cette formation',
            ], 400);
        }

        $result = $this->documentService->genererAttestationFormation($employe, $formation);

        return response()->json($result);
    }

    /**
     * Générer un document pour l'employé connecté (self-service)
     */
    public function selfServiceGenerer(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:attestation_travail,attestation_salaire',
            'params' => 'nullable|array',
        ]);

        $user = $request->user();
        
        if (!$user->employe) {
            return response()->json([
                'success' => false,
                'error' => 'Aucun employé associé à cet utilisateur',
            ], 400);
        }

        $employe = $user->employe;
        $type = $request->type;
        $params = $request->params ?? [];

        // Limiter les types de documents accessibles en self-service
        $typesAutorises = ['attestation_travail', 'attestation_salaire'];
        
        if (!in_array($type, $typesAutorises)) {
            return response()->json([
                'success' => false,
                'error' => 'Type de document non autorisé en self-service',
            ], 403);
        }

        $result = $this->documentService->genererDocument($type, $employe, $params);

        return response()->json($result);
    }
}
