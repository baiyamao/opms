import axios from "axios";

export async function apiLogin(email: string, password: string) {
    const { data } = await axios.post("/api/login", { email, password });

    localStorage.setItem("api_token", data.authorization.token);

    axios.defaults.headers.common["Authorization"] = `Bearer ${data.authorization.token}`;

    return data.user;
}

export async function apiLogout() {
    const token = localStorage.getItem("api_token");

    try {
        if (token) {
            // 通知后端删除当前 token
            await axios.post("/logout", {}, {
                headers: { Authorization: `Bearer ${token}` },
            });
        } else {
            // 如果没有 token，只退出 Web guard
            await axios.post("/logout");
        }
    } catch (error) {
        console.error("退出失败:", error);
    } finally {
        // 前端清理 token
        localStorage.removeItem("api_token");
        delete axios.defaults.headers.common["Authorization"];

        // 跳转回登录页
        window.location.href = "/login";
    }
}
