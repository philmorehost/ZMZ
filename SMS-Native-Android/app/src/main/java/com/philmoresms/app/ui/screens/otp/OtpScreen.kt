package com.philmoresms.app.ui.screens.otp

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.ErrorUtils
import com.philmoresms.app.network.OtpTemplate
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.ui.components.AppTopBar
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await
import kotlin.random.Random

class OtpViewModel : ViewModel() {
    var senderId by mutableStateOf("")
    var recipients by mutableStateOf("")
    var otp by mutableStateOf(Random.nextInt(100000, 999999).toString())
    var templateCode by mutableStateOf("")
    var templates by mutableStateOf<List<OtpTemplate>>(emptyList())
    var sending by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    init {
        loadTemplates()
    }

    fun loadTemplates() {
        viewModelScope.launch {
            try {
                templates = RetrofitClient.apiService.getOtpTemplates().await().templates ?: emptyList()
            } catch (_: Exception) { /* templates are optional */ }
        }
    }

    fun regenerate() {
        otp = Random.nextInt(100000, 999999).toString()
    }

    fun send() {
        error = null
        success = null
        sending = true
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.sendOtp(senderId, recipients, otp, templateCode).await()
                success = res.message ?: "OTP sent successfully"
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to send OTP"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                sending = false
            }
        }
    }
}

@Composable
fun OtpScreen(navController: NavController) {
    val viewModel: OtpViewModel = viewModel()

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("Send OTP", onBack = { navController.popBackStack() })

        Column(
            modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)
        ) {
            FintechInput("Sender ID", viewModel.senderId, { viewModel.senderId = it })
            FintechInput("Recipients (comma-separated)", viewModel.recipients, { viewModel.recipients = it })

            // OTP display + regenerate
            Surface(
                modifier = Modifier.fillMaxWidth().padding(bottom = 20.dp),
                shape = RoundedCornerShape(14.dp),
                color = OtpColor.copy(alpha = 0.1f)
            ) {
                Row(
                    modifier = Modifier.fillMaxWidth().padding(16.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Column {
                        Text("ONE-TIME PIN", fontSize = 11.sp, color = TextSecondary, fontWeight = FontWeight.Bold)
                        Text(viewModel.otp, fontSize = 28.sp, fontWeight = FontWeight.ExtraBold, color = OtpColor, letterSpacing = 6.sp)
                    }
                    Button(onClick = { viewModel.regenerate() }, colors = ButtonDefaults.buttonColors(containerColor = OtpColor)) {
                        Text("Regenerate", color = Color.White)
                    }
                }
            }

            // Template dropdown
            Text("Template (optional)", fontSize = 12.sp, color = TextSecondary, fontWeight = FontWeight.Bold, modifier = Modifier.padding(bottom = 8.dp))
            var expanded by remember { mutableStateOf(false) }
            Box(modifier = Modifier.fillMaxWidth().padding(bottom = 20.dp)) {
                OutlinedButton(onClick = { expanded = true }, modifier = Modifier.fillMaxWidth()) {
                    Text(
                        viewModel.templates.firstOrNull { it.templateCode == viewModel.templateCode }?.templateName ?: "Select template",
                        color = TextPrimary
                    )
                }
                DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                    viewModel.templates.forEach { tpl ->
                        DropdownMenuItem(
                            text = { Text(tpl.templateName ?: tpl.templateCode ?: "") },
                            onClick = {
                                viewModel.templateCode = tpl.templateCode ?: ""
                                expanded = false
                            }
                        )
                    }
                }
            }

            if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))
            if (viewModel.success != null) Text(viewModel.success ?: "", color = Success, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))

            FintechButton(if (viewModel.sending) "Sending..." else "Send OTP", { viewModel.send() }, backgroundColor = OtpColor)
            Spacer(Modifier.height(24.dp))
        }
    }
}
