import numpy as np
import matplotlib.pyplot as plt

# Paramètres physiques
L = 1.0         # Longueur de la barre (m)
T_max = 0.5     # Durée de la simulation (s)
alpha = 0.01    # Diffusivité thermique (m^2/s)

# Discrétisation
nx = 50         # Nombre de points d'espace
nt = 200        # Nombre de pas de temps
dx = L / (nx - 1)
dt = T_max / nt
r = alpha * dt / dx**2

# Vérification de stabilité (r < 0.5 pour méthode explicite)
if r >= 0.5:
    print("ATTENTION : La condition de stabilité n'est pas respectée (r >= 0.5)")

# Conditions initiales
u = np.zeros(nx)
u[int(nx/2)] = 100  # Pic de température au centre

# Conditions aux limites
u[0] = 0
u[-1] = 0

# Sauvegarde pour visualisation
u_all = [u.copy()]

# Simulation dans le temps
for n in range(nt):
    un = u.copy()
    for i in range(1, nx-1):
        u[i] = un[i] + r * (un[i+1] - 2*un[i] + un[i-1])
    u[0] = 0   # Bord gauche
    u[-1] = 0  # Bord droit
    u_all.append(u.copy())

# Animation simple (ou juste visualiser quelques instants)
plt.figure(figsize=(10, 5))
for i in [0, 10, 50, 100, 150, 199]:
    plt.plot(u_all[i], label=f't={i*dt:.3f}s')
plt.xlabel('Position (x)')
plt.ylabel('Température')
plt.title('Propagation de la chaleur dans une barre')
plt.legend()
plt.grid(True)
plt.show()
