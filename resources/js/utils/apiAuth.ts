import axios from "axios";

export async function apiLogin(email: string, password: string) {
    const { data } = await axios.post("/api/login", { email, password });

    localStorage.setItem("api_token", data.authorization.token);

    axios.defaults.headers.common["Authorization"] = `Bearer ${data.authorization.token}`;

    return data.user;
}

export function apiLogout() {
    localStorage.removeItem("api_token");
    delete axios.defaults.headers.common["Authorization"];
}
