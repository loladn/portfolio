import tkinter as tk
from tkinter import filedialog, messagebox
import pandas as pd
import json
import webbrowser
import os
from scipy.stats import chi2_contingency

# dictionnaire pour stocker les dataframes
data_frames = {}
    
#fonction de chargement des données
def charger_excel_data():
    global data_frames

    messagebox.showwarning(title="Avertissement", message="Veuillez à bien sélectionner le fichier Excel contenant les feuilles 'Accident' et 'BD_3quest'")
    file_path = filedialog.askopenfilename(
        title="Sélectionnez un fichier Excel",
        filetypes=[("Excel files", "*.xlsx;*.xls"), ("CSV files", "*.csv")]
    )

    if file_path:
        try:
            data_frames = pd.read_excel(file_path, sheet_name=None)
            creer_btn_feuilles()

        except Exception as e:
            messagebox.showerror("Erreur", f"Une erreur s'est produite lors de la lecture du fichier Excel:\n{e}")

    
#fonction d'affichage des données dans une zone de texte
def afficher_donnees(sheet_name):
    global data_frames

    if sheet_name in data_frames:
        df = data_frames[sheet_name]
        output_text = df.to_string(index=False)

        num_lignes = df.shape[0]
        num_colonnes = df.shape[1]

        titre = f"Données de la feuille : {sheet_name}"
        output_text = f"{titre}\n\n{output_text}"

        txt_output.config(state=tk.NORMAL)
        txt_output.delete('1.0', tk.END)
        txt_output.insert(tk.END, output_text)
        txt_output.config(state=tk.DISABLED)

        info_text.set(f"Feuille : {sheet_name}\nNombre de lignes : {num_lignes}\nNombre de colonnes : {num_colonnes}")
    else:
        messagebox.showwarning("Avertissement", f"La feuille '{sheet_name}' n'a pas été trouvée dans le fichier.")

def changer_feuille(sheet_name):
    afficher_donnees(sheet_name)

#fonction de création de boutons servant à changer la feuille dont les données sont affichées dans la zone de texte
def creer_btn_feuilles():
    for widget in col_gauche.winfo_children():
        widget.grid_forget()

    load_button.grid(row=0, column=0, padx=(0, 10), pady=(0, 10))
    info_label.grid(row=1, column=0, padx=(0, 10), pady=(0, 10))

    btn_width1 = 10
    btn_height1 = 1
    feuilles_a_afficher = ["BD_3quest", "Accident"]
    l=len(feuilles_a_afficher)+3
    for i, sheet_name in enumerate(feuilles_a_afficher):
        btn_feuille = tk.Button(col_gauche, text=sheet_name, width=btn_width1, height=btn_height1, command=lambda s=sheet_name: changer_feuille(s))
        btn_feuille.grid(row=i+3, column=0, padx=10, pady=10)
    btn_pbi = tk.Button(col_gauche, text="Ouvrir la Dataviz Power BI", command=ouvrir_pbi, width=btn_width, height=btn_height, bg="#e12454", fg="#ffffff", padx=10, pady=5, font=("Poppins", 12, "bold"))
    btn_pbi.grid(row=l, column=0, padx=10, pady=10)
    btn_pbi.bind("<Enter>", on_enter)
    btn_pbi.bind("<Leave>", on_leave)

#fonction d'application du traitement statistique au fichier contenant les données
def prog_stat():
    try:
        global data_frames
        df_accident = data_frames["Accident"]
        df_BD_3quest = data_frames["BD_3quest"]

        colonnes_a_supprimer = ['Texte_connaissance_etude', 'Date_remp_foyer', 'Nb_pers_foyer',
                                'Sexe1', 'Type_pers1', 'Annee_naiss1',
                                'Occup_log1', 'Inscrite1', 'Sexe2',
                                'Type_pers2', 'Annee_naiss2', 'Occup_log2',
                                'Inscrite2', 'Sexe3', 'Type_pers3',
                                'Annee_naiss3', 'Occup_log3','Inscrite3', 'Sexe3',
                                'Type_pers4',
                                'Annee_naiss4', 'Occup_log4','Inscrite4', 'Sexe4',
                                'Type_pers5',
                                'Annee_naiss5', 'Occup_log5','Inscrite5', 'Sexe5',
                                'Race_chien',
                                'Taille_com', 'Habitat_voisinage','Surface_exacte_log', 'Chauf',
                               'Abri_jardin',
                               'Plan_eau', 'Compo_foyer','Animaux_dom' ]

        df_BD_3quest = df_BD_3quest.drop(columns=colonnes_a_supprimer)
        df_accident_oui = df_accident.loc[df_accident['Acc'] == 'Oui']
        df_accident_non = df_accident.loc[df_accident['Acc'] == 'Non']

        df_BD_3quest['Age_actuel'] = df_BD_3quest['Age_actuel'].astype(int)
        df_BD_3quest.loc[df_BD_3quest['Age_actuel'] == 1, 'Age_actuel'] = 'Non renseigné'

        df_accident_oui.loc[df_accident_oui['Lieu1'] == 'Habitat, dans mon propre foyer', 'Lieu1'] = 'Habitat'
        df_accident_oui.loc[df_accident_oui['Lieu1'] == 'Habitat, chez une autre personne', 'Lieu1'] = 'Habitat'
        df_accident_oui.loc[~df_accident_oui['Lieu1'].isin(['Habitat']), 'Lieu1'] = 'hors habitat'

        def supprimer_apartir_deuxieme(caracteres):
            return caracteres[:2]

        df_accident_oui['Heure_acc'] = df_accident_oui['Heure_acc'].apply(supprimer_apartir_deuxieme)
        df_accident_oui = df_accident_oui.rename(columns={df_accident_oui.columns[44]: 'Type_acc'})
        df_accident_non = df_accident_non.rename(columns={df_accident_non.columns[44]: 'Type_acc'})

        def extraire_apres_deux_points(cellule):
            try:
                valeur_apres_deux_points = cellule.split('"Type d\'accident":"')[1].strip('"')
                valeur_apres_deux_points = valeur_apres_deux_points[:-3]
                valeur_apres_deux_points = valeur_apres_deux_points.encode().decode('unicode_escape')
                return valeur_apres_deux_points
            except (json.JSONDecodeError, IndexError):
                return None

        df_accident_oui['Type_acc'] = df_accident_oui['Type_acc'].apply(extraire_apres_deux_points)

        Liste_typeacc = ["Chute", "Écrasement, coupure, perforation",
                         "Choc (coup, heurt par contact avec un objet, une personne ou un animal)",
                         "Corps étranger dans un orifice naturel",
                         "Noyade, suffocation, asphyxie",
                         "Intoxication ou autre effet chimique",
                         "Brûlure, refroidissement ou autre effet thermique",
                         "Électricité/rayonnement et effet d'autres ondes d'énergie",
                         "Surmenage physique (sur-sollicitation du corps, faux mouvement)"]

        def remplacer_type_accident(type_acc):
            if (type_acc is not None) and (type_acc not in Liste_typeacc):
                return "Autres accidents"
            else:
                return type_acc

        df_accident_oui['Type_acc'] = df_accident_oui['Type_acc'].apply(remplacer_type_accident)
        df_accident_oui['Heure_acc'] = df_accident_oui['Heure_acc'].astype(int)

        df_accident_oui.loc[df_accident_oui['Heure_acc'] < 12, 'Heure_cat'] = 'Matin'
        df_accident_oui.loc[(df_accident_oui['Heure_acc'] >= 12) & (df_accident_oui['Heure_acc'] < 18), 'Heure_cat'] = 'Après-midi'
        df_accident_oui.loc[df_accident_oui['Heure_acc'] >= 18, 'Heure_cat'] = 'Soir'

        df_accident_f = pd.concat([df_accident_oui, df_accident_non], ignore_index=True)
        df_final = pd.merge(df_accident_f, df_BD_3quest, on='Id_volontaire', how='outer')

        df_accident_subset = df_accident_oui[['Fatigue', 'Heure_cat']]
        contingency_table = pd.crosstab(df_accident_subset['Fatigue'], df_accident_subset['Heure_cat'])
        chi2, p, dof, expected = chi2_contingency(contingency_table)
        if p <= 0.05:
            p1 = 1
        elif p <= 0.1:
            p1 = 2
        else:
            p1 = 0

        df_accident_subset_2 = df_final[['Acc', 'Sexe']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Acc'], df_accident_subset_2['Sexe'])
        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        if p <= 0.05:
            p2 = 1
        elif p <= 0.1:
            p2 = 2
        else:
            p2 = 0

        df_accident_subset_2 = df_accident_f[['Type_acc', 'Heure_cat']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Type_acc'], df_accident_subset_2['Heure_cat'])
        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        if p <= 0.05:
            p3 = 1
        elif p <= 0.1:
            p3 = 2
        else:
            p3 = 0

        df_accident_subset_2 = df_final[['Acc', 'Sit_fam']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Acc'], df_accident_subset_2['Sit_fam'])
        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        if p <= 0.05:
            p4 = 1
        elif p <= 0.1:
            p4 = 2
        else:
            p4 = 0

        df_accident_subset_2 = df_final[['Que faisiez-vous au moment de l\'accident ?', 'Type_acc']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Que faisiez-vous au moment de l\'accident ?'], df_accident_subset_2['Type_acc'])
        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        if p <= 0.05:
            p5 = 1
        elif p <= 0.1:
            p5 = 2
        else:
            p5 = 0

        df_accident_subset_2 = df_final[['Acc', 'Animaux']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Acc'], df_accident_subset_2['Animaux'])

        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        p6 = 1 if p <= 0.05 else 2 if p <= 0.1 else 0

        df_accident_subset_2 = df_final[['Acc', 'Appareils_chauf']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Acc'], df_accident_subset_2['Appareils_chauf'])
        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        if p <= 0.05:
            p7 = 1
        elif p <= 0.1:
            p7 = 2
        else:
            p7 = 0

        df_accident_subset_2 = df_final[['Acc', 'Age_actuel']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Acc'], df_accident_subset_2['Age_actuel'])
        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        if p <= 0.05:
            p8 = 1
        elif p <= 0.1:
            p8 = 2
        else:
            p8 = 0

        df_accident_subset_2 = df_final[['Heure_cat', 'Précisez le lieu de l\'accident :_1']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Heure_cat'], df_accident_subset_2['Précisez le lieu de l\'accident :_1'])
        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        if p <= 0.05:
            p9 = 1
        elif p <= 0.1:
            p9 = 2
        else:
            p9 = 0

        df_accident_subset_2 = df_final[['Acc', 'Type_acc']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Acc'], df_accident_subset_2['Type_acc'])
        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        if p <= 0.05:
            p10 = 1
        elif p <= 0.1:
            p10 = 2
        else:
            p10 = 0

        df_accident_subset_2 = df_final[['Acc', 'Type_log']]
        contingency_table_2 = pd.crosstab(df_accident_subset_2['Acc'], df_accident_subset_2['Type_log'])

        chi2, p, dof, expected = chi2_contingency(contingency_table_2)
        p11 = 1 if p <= 0.05 else 2 if p <= 0.1 else 0
        List_descision = [p1, p2, p3, p4, p5, p6, p7, p8, p9, p10, p11]

        print(List_descision)

        data_frames["Accident"] = df_accident_f
        data_frames["BD_3quest"] = df_BD_3quest

        df_final.to_excel('fichier_correct.xlsx', index=False)

        
        fichier_excel = 'donnees_traitees.xlsx'

        df_final.to_excel(fichier_excel, index=False)
        messagebox.showinfo("Succès", "Les données traitées sont sauvegardées dans le fichier 'donnees_traitees.xlsx'")
        
    except Exception as e:
        messagebox.showerror("Erreur", f"Une erreur est survenue : {e}")

#fonction de lancement des fonctions principales
def exe_donnees():
    charger_excel_data()
    prog_stat()
    afficher_donnees("Accident")

#fonction d'ouverture du fichier de Dataviz sur PowerBI
def ouvrir_pbi():
    os.startfile("powerbi_grp4.pbix")

#fonction pour ouvrir le tutoriel PDF ou vidéo
def ouvrir_tuto():
    choix = messagebox.askquestion("Ouvrir Tutoriel", "Voulez-vous ouvrir le manuel utilisateur (vidéo YouTube) ou le manuel technique (PDF)?", 
                                   icon='question', type='yesnocancel', 
                                   default='cancel', 
                                   detail="Cliquez 'Oui' pour la vidéo ou 'Non' pour le PDF.")

    if choix == 'yes':
        youtube_url = "https://youtu.be/DR9rGqF8870"
        try:
            webbrowser.open(youtube_url)
        except Exception as e:
            messagebox.showerror("Erreur", f"Impossible d'ouvrir la vidéo YouTube:\n{e}")
    elif choix == 'no':
        file_path = "tutoriel.pdf"
        try:
            webbrowser.open(file_path)
        except Exception as e:
            messagebox.showerror("Erreur", f"Impossible d'ouvrir le fichier PDF:\n{e}")
        
#fonctions pour modifier l'apparence d'éléments au survolement du curseur de la souris
def on_enter(event):
    event.widget.config(bg="#98a567", fg="#ffffff")

def on_leave(event):
    event.widget.config(bg="#e12454", fg="#ffffff")


    
#base et positionnement des frames
#création de la fenêtre principale
fenetre = tk.Tk()
fenetre.title("Outil dynamique pour les Accidents de la vie courante")
fenetre.minsize(800, 600)

#définition de la grille pour la fenêtre principale
fenetre.columnconfigure(0, weight=1)
fenetre.rowconfigure(1, weight=1)


#création des frames
header = tk.Frame(fenetre, bg="#f59562")
body = tk.Frame(fenetre, bg="#f4f9fc")
info_frame = tk.Frame(body, bg="#f4f9fc")
col_gauche = tk.Frame(body, bg="#f4f9fc")
txt_output_frame = tk.Frame(body, bg="#f4f9fc")

#positionnement des frames dans la fenêtre principale avec grid
header.grid(row=0, column=0, sticky="ew")
body.grid(row=1, column=0, sticky="nsew")
info_frame.grid(row=0, column=0, columnspan=2, sticky="ew", padx=10, pady=10)
col_gauche.grid(row=1, column=0, sticky="nw")
txt_output_frame.grid(row=1, column=1, pady=10, padx=10, sticky="nsew")

#configuration de la grille pourç les frames
fenetre.columnconfigure(0, weight=1)
header.columnconfigure(0, weight=1)
body.columnconfigure(0, weight=1)
body.rowconfigure(1, weight=1)
info_frame.columnconfigure(0, weight=1)
txt_output_frame.columnconfigure(0, weight=1)
txt_output_frame.rowconfigure(0, weight=1)


btn_width = 18
btn_height = 1

# éléments
# création et placement du label dans le frame header
lblTitre = tk.Label(header, text="Outil de gestion et analyse des accidents de la vie courante en France", font=("Poppins", 16, "bold"), fg="#f4f9fc", bg="#f59562")
lblTitre.grid(row=0, column=0, pady=10)

# ajout du bouton pour ouvrir un PDF dans le cadre approprié
btn_ouvrir_tuto = tk.Button(info_frame, text="Tutoriel", command=ouvrir_tuto, width=15, height=btn_height, font=("Poppins", 13, "bold"), bg="#e12454", fg="#ffffff")
btn_ouvrir_tuto.grid(row=0, column=0, pady=10)

btn_ouvrir_tuto.bind("<Enter>", on_enter)
btn_ouvrir_tuto.bind("<Leave>", on_leave)

# création du bouton pour charger le fichier Excel
load_button = tk.Button(col_gauche, text="Visualiser les données", command=exe_donnees, width=btn_width, height=btn_height, bg="#e12454", fg="#ffffff", padx=10, pady=5, font=("Poppins", 12,"bold"))
load_button.grid(row=0, column=0, padx=10, pady=10)

load_button.bind("<Enter>", on_enter)
load_button.bind("<Leave>", on_leave)

#création de la zone de texte pour afficher les informations de la feuille et le nombre de lignes
info_text = tk.StringVar()
info_label = tk.Label(col_gauche, textvariable=info_text, bg="#f4f9fc", fg="#293b4f", font=("Poppins", 10), anchor="w", justify="left")
info_label.grid(row=2, column=0, sticky="e", padx=(8))

#création de la zone de texte défilante pour afficher les données
txt_output = tk.Text(txt_output_frame, wrap=tk.NONE, width=75, height=30)
txt_output.grid(row=0, column=0)

scroll_y = tk.Scrollbar(txt_output_frame, orient="vertical", command=txt_output.yview)
scroll_y.grid(row=0, column=1, sticky="ns")

scroll_x = tk.Scrollbar(txt_output_frame, orient="horizontal", command=txt_output.xview)
scroll_x.grid(row=1, column=0, sticky="ew")

txt_output.configure(yscrollcommand=scroll_y.set, xscrollcommand=scroll_x.set)

#lancement de la boucle principale
fenetre.mainloop()