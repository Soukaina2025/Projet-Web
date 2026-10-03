#include <iostream>
#include <cstring>
#include <cmath> // Pour M_PI
using namespace std;

// ===============================
// 1) Définition de la classe Forme
// ===============================
class Forme {
protected:
    char nom[15]; // Nom de la forme
public:
    // Constructeur
    Forme(char n[]) {
        strncpy(nom, n, 14);
        nom[14] = '\0';
    }

    // Méthode d'affichage
    virtual void afficher() {
        cout << "Nom de la forme : " << nom << endl;
    }

    // Méthode pure pour calcul de l'aire (abstraite)
    virtual float aire() = 0;

    // Destructeur virtuel
    virtual ~Forme() {
        cout << "Destruction de la forme : " << nom << endl;
    }
};

// ===============================
// 2) Classe Triangle dérivée de Forme
// ===============================
class Triangle : public Forme {
private:
    float base, hauteur;
public:
    // Constructeur
    Triangle(char n[], float b, float h) : Forme(n), base(b), hauteur(h) {}

    // Méthode d'affichage
    void afficher() override {
        Forme::afficher();
        cout << "Base : " << base << ", Hauteur : " << hauteur << endl;
    }

    // Calcul de l'aire
    float aire() override {
        return (base * hauteur) / 2;
    }

    // Destructeur
    ~Triangle() {
        cout << "Destruction du triangle." << endl;
    }
};

// ===============================
// 3) Classe Cercle dérivée de Forme
// ===============================
class Cercle : public Forme {
private:
    float rayon;
public:
    // Constructeur
    Cercle(char n[], float r) : Forme(n), rayon(r) {}

    // Méthode d'affichage
    void afficher() override {
        Forme::afficher();
        cout << "Rayon : " << rayon << endl;
    }

    // Calcul de l'aire
    float aire() override {
        return M_PI * rayon * rayon;
    }

    // Destructeur
    ~Cercle() {
        cout << "Destruction du cercle." << endl;
    }
};

// ===============================
// 4) Classe Rectangle dérivée de Forme
// ===============================
class Rectangle : public Forme {
private:
    float longueur, largeur;
public:
    // Constructeur
    Rectangle(char n[], float l, float L) : Forme(n), longueur(l), largeur(L) {}

    // Méthode d'affichage
    void afficher() override {
        Forme::afficher();
        cout << "Longueur : " << longueur << ", Largeur : " << largeur << endl;
    }

    // Calcul de l'aire
    float aire() override {
        return longueur * largeur;
    }

    // Destructeur
    ~Rectangle() {
        cout << "Destruction du rectangle." << endl;
    }
};

// ===============================
// 5) Programme principal
// ===============================
int main() {
    // Création d'objets automatiques
    cout << "=== Objets automatiques ===" << endl;
    Cercle c1((char *)"Cercle1", 3.5);
    Rectangle r1((char *)"Rectangle1", 4.0, 5.0);
    Triangle t1((char *)"Triangle1", 3.0, 6.0);

    // Affichage et calcul des aires
    c1.afficher();
    cout << "Aire : " << c1.aire() << "\n\n";

    r1.afficher();
    cout << "Aire : " << r1.aire() << "\n\n";

    t1.afficher();
    cout << "Aire : " << t1.aire() << "\n\n";

    // Tableau de pointeurs vers Forme
    cout << "=== Tableau de pointeurs de Forme ===" << endl;
    Forme* formes[3];

    // Remplissage avec des objets dynamiques
    formes[0] = new Cercle((char *)"Cercle2", 2.0);
    formes[1] = new Rectangle((char *)"Rectangle2", 6.0, 7.0);
    formes[2] = new Triangle((char *)"Triangle2", 5.0, 8.0);

    // Affichage des objets du tableau
    for (int i = 0; i < 3; ++i) {
        formes[i]->afficher();
        cout << "Aire : " << formes[i]->aire() << "\n\n";
    }

    // Libération mémoire
    for (int i = 0; i < 3; ++i) {
        delete formes[i];
    }

    return 0;
}

